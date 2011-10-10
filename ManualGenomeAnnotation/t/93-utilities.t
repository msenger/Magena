#!perl -w

package My::Own;  # needed for testing find_file_by_module()

#use Test::More qw(no_plan);
use Test::More tests => 9;

use File::Temp;
use File::Spec;
use Commons::Test;

use Commons::Utilities;
ok($Commons::Utilities::VERSION);
diag( "Testing Commons::Utilities" );

#
# find test file
#
my $testdir = Commons::Test::test_file();
my $testfile = Commons::Test::test_file ('test_config.cfg');

ok (-e $testdir, 'find_file: test dir exists');
ok (-e $testfile, 'find_file: test file exists');

#
# find_file_in_INC()
#
ok (-e Commons::Utilities::find_file_in_INC (File::Spec->catfile ('File', 'Spec.pm')));
is (Commons::Utilities::find_file_in_INC ('it-cannot-exists, hopefully'), undef);

my $fname = 'dummy.file';
my $dir = File::Temp->newdir (CLEANUP => 1);
my $file = File::Spec->catfile ($dir, $fname);
open DUMMY, ">$file" or die "Can't create $file: $!\n";
print DUMMY "dummy\n";
close DUMMY;

unshift @INC, $dir;
ok (-e Commons::Utilities::find_file_in_INC ($fname));
shift @INC;

#
# find_file_by_module()
#
is (Commons::Utilities::find_file_by_module(), undef, 'by_module: no arg');
is (Commons::Utilities::find_file_by_module ('main'), undef, 'by_module: main');

unshift @INC, $testdir;
ok (Commons::Utilities::find_file_by_module ('My::Own', '.cfg'), 'by_module: My::Own');
shift @INC;

1;
__END__
