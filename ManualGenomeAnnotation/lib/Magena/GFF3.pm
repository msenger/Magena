#-----------------------------------------------------------------
# Magena::GFF3
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
# A wrapper around numerous ways how BioPerl deals with GFF files. I
# want just to read and write a simple GFF file...
# -----------------------------------------------------------------

package Magena::GFF3;

use strict;
use warnings;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;
use Commons::Base;
use base qw( Commons::Base );

use Bio::Tools::GFF;
use Bio::SeqFeature::Generic;

#-----------------------------------------------------------------
#
# -----------------------------------------------------------------
sub _create_gffout_handler {
    my $self = shift;
    return if $self->{gffout_handler};

    eval {
	if ($self->outfile) {
	    $self->{gffout_handler} =
		new Bio::Tools::GFF (-file        => '>' . $self->outfile,
				     -gff_version => 3);
	} elsif ($self->outfh) {
	    $self->{gffout_handler} =
		new Bio::Tools::GFF (-fh          => $self->outfh,
				     -gff_version => 3);
	} else {
	    $self->{gffout_handler} =
		new Bio::Tools::GFF (-fh          => \*STDOUT,
				     -gff_version => 3);
	}
    };
    $self->throw ($@) if $@;
}

#-----------------------------------------------------------------
# Create a GFF file with the given $annotations into the output given
# in the constructor (or in a set method) of this instance.
# -----------------------------------------------------------------
sub write {
    my ($self, $annotations) = @_;

    $self->_create_gffout_handler;
    foreach my $annot (@$annotations) {
	my $feature =
	    Bio::SeqFeature::Generic->new (-tag => $annot->attributes);
	$feature->seq_id      ($annot->id     or '.');
	$feature->source_tag  ($annot->source or '.');
	$feature->primary_tag ($annot->type   or '.');
	$feature->start       ($annot->start)  if defined $annot->start;
	$feature->end         ($annot->end)    if defined $annot->end;
	$feature->score       ($annot->score)  if defined $annot->score;
	$feature->strand      ($annot->strand) if defined $annot->strand;
	$feature->frame       ($annot->phase);

	$self->{gffout_handler}->write_feature ($feature);
    }
}

#-----------------------------------------------------------------
# A list of allowed attribute names.
# See Commons::Base for details.
#-----------------------------------------------------------------
{
    my %_allowed =
	(
	 infile  => undef,
	 outfile => { post => sub { shift->{gffout_handler} = undef; } },
	 infh    => undef,
	 outfh   => { post => sub { shift->{gffout_handler} = undef; } },
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

1;
__END__
