#-----------------------------------------------------------------
# Commons::Utilities
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#-----------------------------------------------------------------

package Commons::Utilities;
use strict;
use warnings;

use Commons::Version;
our $VERSION = $Commons::Version::VERSION;

use File::Spec;

#-----------------------------------------------------------------
# Tries to find given file anywhere within @INC directories.
# -----------------------------------------------------------------
sub find_file_in_INC {
    my $filename = shift;
    foreach my $dir (@INC) {
	my $file = File::Spec->catfile ($dir, $filename);
	return $file if -e $file;
    }
    return undef;
}

#-----------------------------------------------------------------
# Guess a filename from the given module name and the given suffix,
# and tries to find it anywhere at @INC
# -----------------------------------------------------------------
sub find_file_by_module {
    my ($pkg, $suffix) = @_;
    return undef unless $pkg;
    return undef if $pkg eq 'main';
    $suffix = '.cfg' unless $suffix;
    $pkg =~ s/::/./g;
    $pkg = lc $pkg;
    while ($pkg) {
	my $file = find_file_in_INC ("$pkg$suffix");
	return $file if $file;
	$pkg =~ s/(\.)?[^\.]+$//;
    }
    return undef;
}

1;
__END__
