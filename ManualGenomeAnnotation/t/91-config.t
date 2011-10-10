#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 40;

use File::Temp qw/ tempfile tempdir /;
use File::Basename;
use File::Spec;
use Commons::Test;

use Commons::Config;
ok($Commons::Config::VERSION);
diag( "Testing Commons::Config" );

my $file = Commons::Test::test_file ('test_config.cfg');
$CFG->add ($file, { tagalo         => 'mabuhay',
		    'tests.slovak' => 'vitajte' },
	   'non-existing-file');

#
# config file(s)
#
my @ok_files = $CFG->ok_files;
is (scalar @ok_files, 1, 'config file');

#
# non-existing config file
#
my @failed_files = keys %{ $CFG->failed_files() };
is (scalar @failed_files, 1, 'failed config file');
is ($failed_files[0], 'non-existing-file', 'failed config file name');

#
# _resolve_file()
#
my $tmpdir = tempdir ( CLEANUP => 1 );
my ($fh, $filename) = tempfile ( DIR => $tmpdir );
print $fh "Dummy\n";
close $fh;

# ...full path
is ($CFG->_resolve_file ($filename), $filename, '_resolve_file: full path');

# ...added to @INC
$filename = basename ($filename);   # full path removed
unshift (@INC, $tmpdir);
is ($CFG->_resolve_file ($filename), File::Spec->catfile ($tmpdir, $filename), '_resolve_file: @INC');
shift @INC;
isnt ($CFG->_resolve_file ($filename), '_resolve_file: @INC');

# ...added to %ENV
$ENV{$Commons::Config::ENV_CONFIG_DIR} = $tmpdir;
is ($CFG->_resolve_file ($filename), File::Spec->catfile ($tmpdir, $filename), '_resolve_file: %ENV');
delete $ENV{$Commons::Config::ENV_CONFIG_DIR};
isnt ($CFG->_resolve_file ($filename), '_resolve_file: %ENV');

#
# get()
#
is ($CFG->get ('czech'),         'ahoj',    'get: value from file');
is ($CFG->get ('tests.english'), 'hello',   'get: block value from file');
is ($CFG->get ('tagalo'),        'mabuhay', 'get: value from add');
is ($CFG->get ('tests.slovak'),  'vitajte', 'get: block value from add');

is ($CFG->get ('mine', 'vot'),   'vot',     'get: default value');
is ($CFG->get ('mine'),          undef,     'get: undef value');

my $params = $CFG->get;  # scalar context
is ($params->{'czech'},         'ahoj',     'get: find value');
is ($params->{'tagalo'},        'mabuhay',  'get: find value');
is ($params->{'tests.english'}, 'hello',    'get: find value');

my @keys = $CFG->get;    # list context
ok (scalar @keys > 7,                        'get: keys');
ok (scalar grep { $_ eq 'czech' } @keys, 1,  'get: keys');
ok (scalar grep { $_ eq 'tagalo' } @keys, 1, 'get: keys');

#
# set()
#
is ($CFG->set (dog => 'Lassie'), 'Lassie', 'set: return value');
is ($CFG->get ('dog'),           'Lassie', 'set: get value');
is ($CFG->set (cat => undef),    undef,    'set: return undef');
is ($CFG->get ('cat'),           undef,    'set: get undef');

#
# dump()
#
ok ($CFG->dump ('DUMP') =~ /^\$DUMP/, 'dump: name');

#
# matching()
#
is (scalar keys %{$CFG->matching()},       0, 'matching: none');
is (scalar keys %{$CFG->matching (undef)}, 0, 'matching: none');
is (scalar keys %{$CFG->matching ('capitals.cz')},  3, 'matching: prefix');
is (scalar keys %{$CFG->matching ('capitals.cz.')}, 3, 'matching: prefix');
is (scalar keys %{$CFG->matching ('capitals.uk')},  2, 'matching: prefix');
is ($CFG->matching ('capitals.sa')->{name}, 'Riyadh', 'matching: prefix');

#
# matching_by_key()
#
is ($CFG->matching_by_key ('country')->{name}, 'Riyadh', 'matching_by_key');
is ($CFG->matching_by_key ('blah-blah')->{name}, undef, 'matching_by_key: none');

#
# report()
#
ok ($CFG->report =~ /All configuration properties/, 'report: all');
ok ($CFG->report ('capitals') =~ /Configuration properties 'capitals'/, 'report: some');

#
# delete()
#
$CFG->delete ('czech');
isnt ($CFG->get ('czech'), 'delete: value');
is ($params->{'tagalo'}, 'mabuhay', 'delete: param still there');
$CFG->delete;
isnt ($CFG->get ('czech'),  'delete: all');
isnt ($CFG->get ('tagalo'), 'delete: all');

1;
__END__
