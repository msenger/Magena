#-----------------------------------------------------------------
# Commons::Test
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#-----------------------------------------------------------------

package Commons::Test;
use strict;
use warnings;

use Commons::Version;
our $VERSION = $Commons::Version::VERSION;

use FindBin qw( $Bin );
use File::Spec;

#-----------------------------------------------------------------
# Return a fully qualified name of the given file in the test
# directory "t/data" - if such file really exists. With no arguments,
# it returns the path of the test directory itself.
# -----------------------------------------------------------------
sub test_file {
    my $file = File::Spec->catfile ('t', 'data', @_);
    return $file if -e $file;
    $file = File::Spec->catfile ($Bin, 'data', @_);
    return $file if -e $file;
    return File::Spec->catfile (@_);
}

#-----------------------------------------------------------------
#
# -----------------------------------------------------------------
sub compare_arrays {
    my ($first, $second) = @_;
    return 0 unless @$first == @$second;
    for (my $i = 0; $i < @$first; $i++) {
	return 0 if $first->[$i] ne $second->[$i];
    }
    return 1;
}

use Test::More;
#-----------------------------------------------------------------
# For each $name/$value pair of attributes in $attr, create an
# instance of the $class (by calling $class->new), call its method
# $name setting the $value. Then call it again and test whether the
# returning value is the same as thr opne just set. Then, do it again
# but set first all $values together, in one call to $class->new.
#
# TBD: It does not work yet with $values of type refarray.
# -----------------------------------------------------------------
sub do_attrs_check {
    my ($class, $attrs) = @_;
    foreach my $aname (keys %$attrs) {
	my $desc = (caller())[1] . " $class set/get: $aname";
	my $avalue = $attrs->{$aname};
	my $obj = $class->new ($aname => $avalue);
	my $result = $obj->$aname;
	Test::More::is ($result, $avalue, $desc); 
    }
    # now, all together
    my $obj = $class->new (%$attrs);
    foreach my $aname (keys %$attrs) {
	my $desc = (caller())[1] . " $class get: $aname";
	my $avalue = $attrs->{$aname};
	my $result = $obj->$aname;
	Test::More::is ($result, $avalue, $desc); 
    }
}

#-----------------------------------------------------------------
# Read given file (or die) and return its content.
# -----------------------------------------------------------------
sub slurp {
    my $file = shift;
    local *FILE;
    open (FILE, $file) or die "Cannot open $file: $!\n";
    local $/ = undef;
    my $file_content .= <FILE>;
    close (FILE);
    return $file_content;
}

1;
__END__
