#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 7;

use FindBin qw( $Bin );
use File::Spec;
use Commons::Test;
use Magena::Server;

ok(1);
diag( "Testing Magena::Server" );

#
# _absolutize()
#

do_absolute ('abc',  'abc',                       '_absolutize: absolute');
do_absolute ('/abc', '/abc',                      '_absolutize: begin slash');
do_absolute ('abc/', 'abc/',                      '_absolutize: ending slash');
do_absolute (File::Spec->catfile ($Bin, './abc'), './abc', '_absolutize: dot');

sub do_absolute {
    my ($expected_dir, $dir, $msg) = @_;
    is (Magena::Server::_absolutize ($dir), $expected_dir, $msg);
}

#
# get_input()
#
use CGI;
my $input = 'klm';

do_input ("/$input",  'br', "/$input", 'do_input: starting slash');
do_input ("/$input",  'br', $input,    'do_input: no starting slash');

sub do_input {
    my ($expected, $param, $value, $msg) = @_;
    my $cgi = CGI->new ( { $param => $value } );
    my ($full, $part) = Magena::Server::get_input ($cgi);
    is ($part, $expected, $msg);
}

1;
__END__
