#!/usr/bin/perl -w
#

use strict;
use warnings;
use lib "/home/senger/bioperl-live";
use Bio::SearchIO v1.6.9;
use Data::Dumper;

my $in = new Bio::SearchIO(-format => 'blast', 
                           -file   => '/home/senger/magena/data/cellulosebio_bact/s5_blast_output/s4_candidate_01_mpiblast1_in_uniprot.out.part0');

my @results = ();
while( my $result = $in->next_result ) {

#    print Dumper ($result) unless $count++ > 0;
    my $result_lite = {};
    $result_lite->{id} = $result->query_name;
    $result_lite->{desc} = $result->query_description;
    $result_lite->{length} = $result->query_length;
    $result_lite->{hits} = [];

    while( my $hit = $result->next_hit ) {
	my $hit_lite = {};
	$hit_lite->{id} = $hit->name;
	$hit_lite->{desc} = $hit->description;
	$hit_lite->{length} = $hit->length;
	$hit_lite->{significance} = $hit->significance;
	push (@{ $result_lite->{hits} }, $hit_lite);
    }
    push (@results, $result_lite);
}
print Dumper (\@results);

__END__

    while( my $hsp = $hit->next_hsp ) {
#	print Dumper ($hsp) unless $count++ > 0;
      ## $hsp is a Bio::Search::HSP::HSPI compliant object
#      if( $hsp->length('total') > 50 ) {
#        if ( $hsp->percent_identity >= 75 ) {

#          print "Query=",   $result->query_name,
#            " Hit=",        $hit->name,
#            " Length=",     $hsp->length('total'),
#            " Percent_id=", $hsp->percent_identity, "\n";

#        }
#      }
    }  
  }
}
