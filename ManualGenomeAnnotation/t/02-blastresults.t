#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 48;

use Magena::BlastResults;
use Commons::Test;
ok (1);
diag( "Testing Magena::BlastResults" );

use Commons::Config;
$CFG->add (Commons::Test::test_file ('test_config.cfg'));

my $input = Commons::Test::test_file ('blast_results.bls');

#
# _parse()
#
do_parse (Magena::BlastResults->new (file => $input));
do_parse (Magena::BlastResults->new ());

#
# list_queries()
#
do_list ( 0, Magena::BlastResults->new ( file => undef ) );
do_list ( 5, Magena::BlastResults->new ( file => $input ) );
do_list ( 5, Magena::BlastResults->new ( files => [$input] ) );
do_list (10, Magena::BlastResults->new ( files => [$input, $input] ) );
do_list (15, Magena::BlastResults->new ( files => [$input, $input], file => $input ) );
do_list ( 5, Magena::BlastResults->new ( files => undef, file => $input ) );

my $list = Magena::BlastResults->new (file => $input )->list_queries;
is (ref ($list->[0]), 'HASH', 'list_queries: Correct return type');
is ($list->[0]->{BR_ID()}, 'atbrine08a1_gb5urfc03glgby', 'list_queries: id');
is ($list->[0]->{BR_DESCRIPTION()}, 'ATBRINE08A1_GB5URFC03GLGBY', 'list_queries: desc');
is ($list->[0]->{BR_SEQLEN()}, 491, 'list_queries: length');

is ($list->[0]->{BR_BEST_EVALUE()}, 3e-25, "list_queries: best_e");
is ($list->[1]->{BR_BEST_EVALUE()}, 6e-22, "list_queries: best_e");
is ($list->[2]->{BR_BEST_EVALUE()}, 3e-16, "list_queries: best_e");
is ($list->[3]->{BR_BEST_EVALUE()}, 3e-21, "list_queries: best_e");
is ($list->[4]->{BR_BEST_EVALUE()}, 1e-15, "list_queries: best_e");

is ($list->[0]->{BR_BEST_DESCRIPTION()}, 'GAF domain/GGDEF domain protein', 'list_queries: best_d');
is ($list->[1]->{BR_BEST_DESCRIPTION()}, 'Diguanylate cyclase with PAS/PAC sensor', 'list_queries: best_d');
is ($list->[2]->{BR_BEST_DESCRIPTION()}, 'Two-component response regulator protein', 'list_queries: best_d');
is ($list->[3]->{BR_BEST_DESCRIPTION()}, 'Probable two-component response regulator', 'list_queries: best_d');
is ($list->[4]->{BR_BEST_DESCRIPTION()}, 'Diguanylate cyclase', 'list_queries: best_d');

sub do_parse {
    my $br = shift;
    $br->_parse();
    is ($br->{parsing_done}, 1, "_parse: Parsing done");
}

sub do_list {
    my ($expected, $br) = @_;
    my $list = $br->list_queries;
    isnt ($list, undef,    "list_queries: Non-empty result");
    is (@$list, $expected, "list_queries: Number of results");
}

#
# get_query()
#
my $br = Magena::BlastResults->new (file => $input );
my @ids = (
    'atbrine08a1_gb5urfc03glgby',
    'atbrine08a1_gb5urfc03gmmde',
    'at0050m01_f6a88sk01bcpfy',
    'at0200m01a1_gattb1c02hncfg',
    'at0200m01a1_gattb1c02jf18y');
for (my $i = 0; $i < @ids; $i++) {
    do_query ($ids[$i], $i, $ids[$i], $br);
}
do_query (undef, undef, 'absoluTELY_not-FOUNDable', $br);

sub do_query {
    my ($expected_id, $expected_index, $qid, $br) = @_;
    my $result = $br->get_query ($qid);
    if ($expected_id) {
	is ($expected_id, $result->id,  'get_query: Correct ID (1)');
    } else {
	is (undef, $result,             'get_query: undef (1)');
    }
    my ($index, $result2) = $br->get_query ($qid);
    if ($expected_id) {
	is ($expected_id, $result2->id, 'get_query: Correct ID (2)');
	is ($expected_index, $index,    'get_query: Index');
    } else {
	is (undef, $result2,            'get_query: undef (2)');
	is (undef, $index,              'get_query: undef (3)');
    }
}

#
# testing error conditions
#
eval { Magena::BlastResults->new (dummy => 'yes') };
ok ($@ =~ /No such method: Magena::BlastResults::dummy/, "Unknown argument error");

1;
__END__
