#!/usr/bin/perl -w
#

use strict;
use warnings;
#use feature qw( say );
sub say {}
use Data::Dumper;

use Bio::FeatureIO;
use Bio::Tools::GFF;
use Bio::SeqIO;

my $input = ($ARGV[0] or 'data/xp_001105514.txt');
my $seqio = new Bio::SeqIO (-format => 'genbank',
			    -file   => $input);
my $output = 'output.gff';
my $gffout = new Bio::Tools::GFF (-file        => ">$output" ,
				  -gff_version => 3);

while( my $seq = $seqio->next_seq ) {

    say "ID:          ", $seq->display_id;
    say "Alphabet:    ", $seq->alphabet;
    say "Accession:   ", $seq->accession_number();
    say "Version:     ", $seq->seq_version();
    say "Keywords:    ", $seq->keywords();
    say "Length:      ", $seq->length();
    say "Description: ", $seq->desc();
    say "Primary ID:  ", $seq->primary_id();
    say "Sequence:\n", $seq->seq;

    say "\nFeatures:\n";

    foreach my $feat ( $seq->top_SeqFeatures() ) {
    	$gffout->write_feature ($feat);

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
	
    	# say "Sub-features:";
    	# foreach my $subfeat ( $feat->get_SeqFeatures() ) {
    	#     say $subfeat;
    	# }
	
    	say "---------------------------";
	say $feat->gff_string;
    	say "---------------------------";
    }

#    $seq->write_GFF (\*STDOUT);
}

__END__
