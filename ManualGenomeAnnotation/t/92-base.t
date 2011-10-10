#!perl -w

package My::Own;  # needed for testing _parse_import_args()

#use Test::More qw(no_plan);
use Test::More tests => 66;

use File::Spec;
use Commons::Test;

use Commons::Base;
ok($Commons::Base::VERSION);
diag( "Testing Commons::Base" );

#
# _parse_import_args()
#
do_parse ('1', ['a', 'b'],
	  { cfg_args    => [],
	    export_args => ['a', 'b'],
	    logging     => undef });
do_parse ('2', ['-logname', 'L'],
	  { cfg_args    => [],
	    export_args => [],
	    logging     => 'L' });
do_parse ('3', ['-logname=L'],
	  { cfg_args    => [],
	    export_args => [],
	    logging     => 'L' });
do_parse ('4', ['-cfg=config1', '-cfg', 'config2', 'b', 'c', '-logname=L'],
	  { cfg_args    => ['config1', 'config2'],
	    export_args => ['b', 'c'],
	    logging     => 'L'});
do_parse ('5', ['-cfg=config1', '-forceDefaultCfg'],
	  { cfg_args    => ['config1'],
	    export_args => [],
	    logging     => undef});
my $testdir = Commons::Test::test_file();
my $test_def_cfg = File::Spec->catfile ($testdir, "my.own.cfg");
unshift @INC, $testdir;
do_parse ('6', [],
	  { cfg_args    => [$test_def_cfg],
	    export_args => [],
	    logging     => undef});
do_parse ('7', ['-forceDefaultCfg'],
	  { cfg_args    => [$test_def_cfg],
	    export_args => [],
	    logging     => undef});
do_parse ('8', ['-noDefaultCfg'],
	  { cfg_args    => [],
	    export_args => [],
	    logging     => undef});
$Commons::Base::Nocfg = 0;   # without this reset '8a' would fail
do_parse ('8a', [],
	  { cfg_args    => [$test_def_cfg],
	    export_args => [],
	    logging     => undef});
do_parse ('9', ['-noDefaultCfg', '-forceDefaultCfg'],
	  { cfg_args    => [$test_def_cfg],
	    export_args => [],
	    logging     => undef});

do_parse ('10', ['-cfg', 'config1', '-forceDefaultCfg'],
	  { cfg_args    => [$test_def_cfg, 'config1'],
	    export_args => [],
	    logging     => undef});
do_parse ('11', ['-cfg', 'config1', '-noDefaultCfg'],
	  { cfg_args    => ['config1'],
	    export_args => [],
	    logging     => undef});
do_parse ('12', ['-cfg', 'config1', '-noDefaultCfg', '-forceDefaultCfg'],
	  { cfg_args    => [$test_def_cfg, 'config1'],
	    export_args => [],
	    logging     => undef});
shift @INC;

sub do_parse {
    my ($t, $args, $expected) = @_;
    my $caller_pkg = caller();
    my $result = Commons::Base::_parse_import_args ($caller_pkg, @$args);
    ok ($result,                                  "_parse_import: non-empty-result ($t)");
    is (scalar keys %$result, 3,                    "_parse_import: number of keys ($t)");
    # if ($t == 10) {
    # 	diag "CFG: " . join ("--", @{ $result->{cfg_args} }); 
    # }
    ok (Commons::Test::compare_arrays ($expected->{cfg_args},
				       $result->{cfg_args}),     "_parse: cfg args ($t)");
    ok (Commons::Test::compare_arrays ($expected->{export_args},
				       $result->{export_args}),  "_parse: exp args ($t)");
    is ($result->{logging}, $expected->{logging},                "_parse: logging ($t)");
}

1;
__END__
