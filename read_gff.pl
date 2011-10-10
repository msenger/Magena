#!/usr/bin/perl -w
#
# Reading features from a GFF file.
# ---------------------------------

use strict;
use warnings;
#use feature qw( say );
sub say {}
use Data::Dumper;

use Bio::FeatureIO;

die "Usage: $0 <file.gff>\n" unless @ARGV > 0;
my $gff_in =
    Bio::FeatureIO->new (-file   => $ARGV[0] ,
			 -format => "GFF");
my $gff_out1 =
    Bio::FeatureIO->new (-file    => ">test-out-1.gff",
			 -version => 3,
			 -format  => "GFF");

use Bio::Tools::GFF;
my $gff_out2 = new Bio::Tools::GFF (-file        => ">test-out-2.gff" ,
				    -gff_version => 3);

while ( my $feat = $gff_in->next_feature() ) {

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
    # 	say $subfeat;
    # }

    say "---------------------------";

#    $gff_out1->write_feature ($feat);
    $gff_out2->write_feature ($feat);

}


__END__
