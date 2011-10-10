#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 18;

use Commons::Test;

use Magena::BlastResults;
use Magena::GFF3;
use IO::String;

ok(1);
diag( "Testing Magena::GFF3" );

Commons::Test::do_attrs_check
    ("Magena::GFF3",
     { infile  => 'a',
       outfile => 'b',
       infh    => 'c',
       outfh   => 'd',
     });

#
# _create_gffout_handler
#
do_create (Magena::GFF3->new);
do_create (Magena::GFF3->new (outfile => '/tmp/x'));
do_create (Magena::GFF3->new (outfh   => \*STDOUT));
do_create (Magena::GFF3->new (outfile => '/tmp/y', outfh   => \*STDOUT));

sub do_create {
    my $gff = shift;
    $gff->_create_gffout_handler;
    ok ($gff->{gffout_handler}, 'gffout handler exists');
}

#
# write()
#
my $input = Commons::Test::test_file ('blast_results.bls');

do_write ($input, 'output_br_00.gff');
do_write ($input, 'output_br_00.gff', undef);
do_write ($input, 'output_br_00.gff', 0);
do_write ($input, 'output_br_03.gff', 3);
do_write ($input, 'output_br_99.gff', 99);

sub do_write {
    my ($input, $output_filename, $index) = @_;

    my $output;
    my $io = IO::String->new ($output);
    my $gff = Magena::GFF3->new (outfh => $io);

    my $br = Magena::BlastResults->new ( file => $input );
    if (defined $index) {
	$gff->write ($br->_get_annotations ($index));
    } else {
	$gff->write ($br->_get_annotations);
    }

    my $expected_output_file = Commons::Test::test_file ($output_filename);
    my $expected_output = Commons::Test::slurp ($expected_output_file);
    is ($output, $expected_output, "write: $expected_output_file");
}

1;
__END__
