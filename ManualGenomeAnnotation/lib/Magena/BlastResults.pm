#-----------------------------------------------------------------
# Magena::BlastResults
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
# It parses given blast results and returns various pieces of it. It
# uses third-party blast parser - but it wraps its results in
# structures more convenient for Magena.
#-----------------------------------------------------------------

package Magena::BlastResults;

use strict;
use warnings;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;
use Commons::Base;
use base qw( Commons::Base Exporter );

use Magena::Utilities;
use Magena::BlastResult;
use Magena::BlastHit;
use Magena::BlastHSP;
use Bio::SearchIO v1.6.9;
use File::Temp;

# this must be here because the super-class has its own import()
sub import {
    shift->export_to_level (1, @_);
}

# (exported) constants used by list_queries
use constant {
    BR_ID               => 'id',
    BR_BEST_EVALUE      => 'best_e',
    BR_DESCRIPTION      => 'desc',
    BR_BEST_DESCRIPTION => 'best_desc',
    BR_SEQLEN           => 'length',

    BR_SORT_BY_ID     => 'by_id',
    BR_SORT_BY_EVALUE => 'by_ev',
    BR_SORT_BY_SEQLEN => 'by_sl',
};

our @EXPORT = qw( BR_ID BR_BEST_EVALUE BR_DESCRIPTION BR_BEST_DESCRIPTION BR_SEQLEN BR_SORT_BY_ID BR_SORT_BY_EVALUE BR_SORT_BY_SEQLEN);

#-----------------------------------------------------------------
# A list of allowed attribute names.
# See Commons::Base for details.
#-----------------------------------------------------------------
{
    my %_allowed =
	(
	 file  => undef,
	 files => { is_array => 1 },
	 );

    sub _accessible {
	my ($self, $attr) = @_;
	exists $_allowed{$attr} or $self->SUPER::_accessible ($attr);
    }
    sub _attr_prop {
	my ($self, $attr_name, $prop_name) = @_;
	my $attr = $_allowed {$attr_name};
	return ref ($attr) ? $attr->{$prop_name} : $attr if $attr;
	return $self->SUPER::_attr_prop ($attr_name, $prop_name);
    }
}

#-----------------------------------------------------------------
# init
#-----------------------------------------------------------------
sub init {
    my ($self) = shift;
    $self->SUPER::init();
    $self->{parsing_done} = 0;   # will change after input parsed
}

#-----------------------------------------------------------------
# Called only once; keeps parsed results in memory.
#-----------------------------------------------------------------
sub _parse {
    my ($self) = shift;
    return if $self->{parsing_done};

    # read and store the blast results from all files given
    my @results = ();
    foreach my $file (Magena::Utilities->_files ($self)) {
	my $in;
	eval {
	    $in = new Bio::SearchIO ( -format => 'blast', 
				      -file   => $file )
	};
	$self->throw ($@) if $@;
	# my $debug_counter = 0;
	while( my $result = $in->next_result ) {

	    # use Data::Dumper;
	    # print Dumper ($result) if $debug_counter++ == 0;

	    my $br = Magena::BlastResult->new
		(id          => $result->query_name,
		 description => $result->query_description,
		 length      => $result->query_length
		);
	    $br->hits ([]);
	    $br->{parent_obj} = $self;
	    while( my $hit = $result->next_hit ) {
		my $bh = Magena::BlastHit->new
		    (id          => $hit->name,
		     description => $hit->description,
		     length      => $hit->length,
		     evalue      => $hit->significance,
		     # score       => $hit->raw_score,
		     # strand      => $hit->strand ('query'),
		    );
		{
		    # ignore the following warnings here:
		    # --------------------- WARNING ---------------------
		    # MSG: There is no HSP data for hit 'P_505'.
		    # You have called a method (Bio::Search::SearchUtils::tile_hsps)
		    # that requires HSP data and there was no HSP data for this hit,
		    # most likely because it was absent from the BLAST report.
		    # Note that by default, BLAST lists alignments for the first 250 hits,
		    # but it lists descriptions for 500 hits. If this is the case,
		    # and you care about these hits, you should re-run BLAST using the
		    # -b option (or equivalent if not using blastall) to increase the number
		    # of alignments.
		    my $save_verbose = $hit->verbose; $hit->verbose (-1);
		    if ($hit->raw_score) {
			$bh->score ($hit->raw_score);
		    }
		    if ($hit->strand ('query')) {
			$bh->strand ($hit->strand ('query'));
		    }
		    $hit->verbose ($save_verbose);
		}
		my $hsps = [];
		while (my $hsp = $hit->next_hsp()) {

		    # use Data::Dumper;
		    # print STDERR Dumper ($hsp);

		    my $bhsp = Magena::BlastHSP->new
			(algorithm      => $hsp->algorithm,
			 frac_identical => $hsp->frac_identical,
			 frac_conserved => $hsp->frac_conserved,
			 gaps           => $hsp->gaps,
			 query_seq      => $hsp->query_string,
			 hit_seq        => $hsp->hit_string,
			 homology_seq   => $hsp->homology_string,
			 rank           => $hsp->rank,
			 evalue         => $hsp->evalue,
			 bits           => $hsp->bits,
			 score          => $hsp->score,
			 query_length   => $hsp->length ('query'),
			 query_start    => $hsp->start ('query'),
			 query_end      => $hsp->end ('query'),
			 hit_length     => $hsp->length ('hit'),
			 hit_start      => $hsp->start ('hit'),
			 hit_end        => $hsp->end ('hit'),
			);
		    push (@$hsps, $bhsp);
		}
		$bh->hsps ($hsps);

		$br->add_hits ($bh);
	    }
	    push (@results, $br);
	}
    }

    $self->{results} = \@results;
    $self->{parsing_done} = 1;
}

#-----------------------------------------------------------------
# Called only once; keeps pointers to raw data in memory.
#-----------------------------------------------------------------
sub _split {
    my ($self) = shift;
    return if $self->{splitting_done};

    my $dir = File::Temp->newdir (CLEANUP => 1);
    my $outputs = Magena::Utilities->split_blast_results (
    	files      => Magena::Utilities->_files ($self),
    	max        => 1,
    	output_dir => $dir,
    	);

    $self->{raw_refs} = $outputs;
    $self->{splitting_done} = 1;
}

#-----------------------------------------------------------------
#
# -----------------------------------------------------------------
sub _assign_raw_results {
    my ($self) = @_;
    $self->_parse;
    $self->_split;
    warn "Raw results do not match the parsed results. Strange...\n"
	if @{ $self->{raw_refs} } != @{ $self->{results} };
    for (my $i = 0; $i < @{ $self->{results} }; $i++) {
	$self->{results}->[$i]->raw_ref ($self->{raw_refs}->[$i]);
    }
}

#-----------------------------------------------------------------
# Find IDs of all query sequences in the given blast results.
#
# Return an refarray of hashrefs, each of them has at least the key
# BR_ID, and possibly also keys BR_BEST_EVALUE (with the best expect
# value for this query sequence), BR_DESCRIPTION (with the query
# sequence description), and BR_SEQLEN (with the query sequence
# length). And key BR_BEST_DESCRIPTION (with the description from the
# best hit).
#
# Optional $sorting ia a constant identifying how to sort the returned
# elements.
# -----------------------------------------------------------------
sub list_queries {
    my ($self, $sorting) = @_;
    $self->_parse;
    my @list = ();
    foreach my $result (@{ $self->{results} }) {
	my $elem = {};
	$elem->{BR_ID()} = $result->id;
	$elem->{BR_DESCRIPTION()} = ($result->description or '');
	$elem->{BR_SEQLEN()} = $result->length if $result->length;
	my $best_e = 1000;
	my $best_desc = '';
	foreach my $hit (@{ $result->hits }) {
	    my $evalue = $hit->evalue;
	    if ($evalue < $best_e) {
		$best_e = $evalue;
		$best_desc = $hit->description;
	    }
	}
	$elem->{BR_BEST_EVALUE()} = $best_e;
	$elem->{BR_BEST_DESCRIPTION()} = $best_desc;
	push (@list, $elem);
    }
    return \@list unless $sorting;
    return [ sort { $a->{BR_ID()} cmp $b->{BR_ID()} } @list ]
	if $sorting eq BR_SORT_BY_ID;
    return [ sort { $a->{BR_BEST_EVALUE()} <=> $b->{BR_BEST_EVALUE()} } @list ]
	if $sorting eq BR_SORT_BY_EVALUE;
    return [ sort { $a->{BR_SEQLEN()} <=> $b->{BR_SEQLEN()} } @list ]
	if $sorting eq BR_SORT_BY_SEQLEN;
    warn "Unknown sorting criterion: $sorting\n" and return \@list;
}

#-----------------------------------------------------------------
# In scalar context: return found query (given as $gid).
#
# In array context: return [index, result] where 'result' is the same
# as what returned in scalar context, and 'index' is the index number
# of this result in the whole blast results.
#
# Return undef (or (undef, undef) ) if $qid was not found.
# -----------------------------------------------------------------
sub get_query {
    my ($self, $qid) = @_;
    $self->_parse;
    my $counter = 0;
    foreach my $result (@{ $self->{results} }) {
	if ($result->id eq $qid) {
	    if (wantarray) {
		return ($counter, $result);
	    } else {
		return $result;
	    }
	}
	$counter++;
    }
    if (wantarray) {
	return (undef, undef);
    } else {
	return undef;
    }
}

#-----------------------------------------------------------------
# Return all results (as a refarray of BlastResult objects).
# -----------------------------------------------------------------
sub get_queries {
    my $self = shift;
    $self->_parse;
    return $self->{results};
}

#-----------------------------------------------------------------
# Return annotations created from all results (as a refarray of
# Annotation objects). This is rather for debugging - it just creates
# one annotation per blast query, using values from its best hit
# (well, the order number, starting from 0, can be given in $index).
# -----------------------------------------------------------------
use Magena::Annotation;
sub _get_annotations {
    my ($self, $index) = @_;
    $index = 0 unless defined $index;
    $self->_parse;

    my $source = CFG->get ('gff.source', 'UNKNOWN');
    my $type = CFG->get ('gff.type', 'UNKNOWN');

    my $results = [];
    foreach my $br (@{ $self->get_queries }) {
	my $annot = Magena::Annotation->new
	    ( id     => $br->id,
	      source => $source,
	      type   => $type,
	      start  => 1,
	      end    => $br->length,
	    );
	my $hits = $br->hits;
	my $attrs = {};
	if (@$hits > $index) {
	    my $hit = $hits->[$index];
	    $annot->score ($hit->evalue);
	    $annot->strand ($hit->strand);

	    $attrs->{Name} = $hit->description if $hit->description;
	    $attrs->{Alias} = $hit->gn if $hit->gn;
	    $attrs->{ATTR_TAXON()} = $hit->os if $hit->os;
	    # TBD: $attrs->{ATTR_CREATED()} = ...;
	    # TBD: $attrs->{ATTR_MODIFIED()} = ...;
	    # TBD: $attrs->{ATTR_CURATOR()} = ...;
	}
	$annot->attributes ($attrs);
	push (@$results, $annot);
    }
    return $results;
}

1;
__END__
