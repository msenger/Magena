#!/usr/bin/perl -w
#

use strict;
use warnings;
use feature qw( say );
use Data::Dumper;

# TBD: these two "use"s work only in this order... why?
use Magena::BlastResults;
use Magena::GFF3;

#my $gff = Magena::GFF3->new (outfh => \*STDOUT);
use IO::String;
my $output;
my $io = IO::String->new ($output);
my $gff = Magena::GFF3->new (outfh => $io);
my $br = Magena::BlastResults->new ( file => 'ManualGenomeAnnotation/t/data/blast_results.bls' );
$gff->write ($br->_get_annotations);
print $output;

__END__
use Magena::Annotation;

my $id     = 'a';
my $source = 'b';
my $type   = 'c';
my $start  = 25;
my $end    = 50;
my $score  = 3e-25;
my $strand = '+';
my $phase  = 'd';
my $annot = Magena::Annotation->new (id     => $id,
				     source => $source,
				     type   => $type,
				     start  => $start,
				     end    => $end,
				     score  => $score,
				     strand => $strand,
				     phase  => $phase);
my $attrs = { ATTR_TAXON()    => 'k',
	      ATTR_CREATED()  => 'l',
	      ATTR_MODIFIED() => 'm',
	      ATTR_CURATOR()  => 'n',
};
foreach my $key (keys %$attrs) {
    $annot->attributes ({ $key => $attrs->{$key} });
}

use Magena::GFF3;
#my $gff = Magena::GFF3->new (outfile => 'test-out.gff');
my $gff = Magena::GFF3->new (outfh => \*STDOUT);
$gff->write ([$annot]);
#$annot->write (\*STDOUT);

__END__

use Bio::FeatureIO;

# reading features from a GFF file
my $gff_in  = Bio::FeatureIO->new(-file => "data/hemsa.gff" , -format => "GFF");

while ( my $feat = $gff_in->next_feature() ) {
###    push (@cds, $feat) if $feat->primary_tag =~ /CDS/;  # or whatever

#    say "Primary ID:  ", $feat->primary_id;
    say "Start:       ", $feat->start;
    say "End:         ", $feat->end;
    say "Strand:      ", $feat->strand;
    say "Primary tag: ", $feat->primary_tag;
    say "Source tag:  ", $feat->source_tag;
    say $feat->location->to_FTstring();
    say "Tags:";

    foreach my $tag ( $feat->get_all_tags() ) {
	say "\t", $tag, " => ", join (' | ', $feat->get_tag_values($tag));
    }

    say "Sub-features:";
    foreach my $subfeat ( $feat->get_SeqFeatures() ) {
	say $subfeat;
    }

    say "---------------------------";
}

__END__

For reading, you want to use Bio::FeatureIO  as follows:


use Bio::FeatureIO;

my $gff_in  = Bio::FeatureIO->new(-file => "test.gff" , -format => "GFF");

while ( my $feat = $gff_in->next_feature() ) {
   push (@cds, $feat) if $feat->primary_tag =~ /CDS/;  # or whatever
}



Aso, depending on what kind of data you have in-hand when you start,
you might want to use BioPerl's SeqIO to get your Sequence Feature
objects, and then Bio::Tools::GFF is likely the place you want to look
for writing.  Sample code:


use Bio::Tools::GFF;
use Bio::SeqIO;

my ($seqfile) = @ARGV;

my $seqio = new Bio::SeqIO(-format => 'genbank',
                          -file   => $seqfile);
my $count = 0;
while( my $seq = $seqio->next_seq ) {
   my $gffout = new Bio::Tools::GFF(-file => ">$fname" ,
                                    -gff_version => 3);

   # MARTIN - this is where you get the seq feature that
   # corresponds to a GFF3 line, or just make one from scratch
   # (see comment below)
   foreach my $feature ( $seq->top_SeqFeatures() ) {
       $gffout->write_feature($feature);
   }
}


If you don't have sequences to begin with, then simply create some
flavour of Bio::SeqFeature from scratch
(http://doc.bioperl.org/releases/bioperl-current/bioperl-live/Bio/SeqFeatureI.html)
and then write that out using the code above.
