#-----------------------------------------------------------------
# Magena::Server
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
# Utilities used by cgi-bin scripts.
# TBD: perhaps later make use of this module in mod_perl.
#-----------------------------------------------------------------
package Magena::Server;

use warnings;
use strict;
use Magena::Version;
our $VERSION = $Magena::Version::VERSION;

use CGI;
use CGI::Carp qw( fatalsToBrowser );
use FindBin qw( $Bin );
use File::Spec;

#-----------------------------------------------------------------
# Error handling
#-----------------------------------------------------------------
# use CGI::Carp qw( set_die_handler );
# BEGIN {
#     sub handle_errors {
# 	my $msg = shift;
# 	print "content-type: text/html\n\n";
# 	print "<h1>Oh gosh</h1>";
# 	print "<p>Got an error: $msg</p>";
#     }
#     set_die_handler(\&handle_errors);
# }

#-----------------------------------------------------------------
# Configuration
#-----------------------------------------------------------------
use Commons::Base;
my $root_data = _absolutize (CFG->get ('web.root.data', '../datadir'));

#-----------------------------------------------------------------
#
#-----------------------------------------------------------------
sub get_data_root {
    return $root_data;
}

#-----------------------------------------------------------------
#
#-----------------------------------------------------------------
sub get_template_dir {
    return _absolutize (CFG->get ('web.template.dir', '../templates'));
}

# if $dir is a relative path, prepend $Bin to it
sub _absolutize {
    my $dir = shift; 
    if ($dir =~ /^[.]/) {
	my $script_dir = $Bin;
	$script_dir =~ s/ajax[\/]?$//;
	$dir = File::Spec->catfile ($script_dir, $dir);
    }
    return $dir;
}

#-----------------------------------------------------------------
# Retrieve an input file name from the given $cgi instance (by name
# given in $param, or by default using 'br'); croak if such parameter
# does not exist; prepend it with the root data (taken from the
# configuration) and return it.
#
# In array context, it returns actually two file names. The first one
# the same as in the scalar context (as described above), the second
# one is without root data dir prepended (but only with a slash at the
# begining).
# -----------------------------------------------------------------
sub get_input {
    my ($cgi, $param) = @_;

    my $input = $cgi->param ($param or 'br');

    # because of files with spaces in the name
    # (I thought that this unescape is done already by the CGI...)
    use CGI::Util;
    $input = CGI::Util::unescape ($input);

    croak "No input file given; or input file lost.\n"
	unless $input;
    $input = "/$input"
	unless $input =~ m{^/};  # because it will be prepended by $root_data

    if (wantarray) {
	return ("$root_data$input", $input);
    } else {
	return "$root_data$input";
    }
}

#-----------------------------------------------------------------
# Retrieve one or more input file names from the given $cgi instance
# (by name given in $param, or by default using 'br'); croak if such
# parameter does not exist; prepend each file it with the root data
# (taken from the configuration) and return it.
#
# In array context, it returns actually two arrays of file names. The
# first one is the same as in the scalar context (as described above),
# the second one is without root data dir prepended (but only with a
# slash at the begining of each file).
# -----------------------------------------------------------------
sub get_inputs {
    my ($cgi, $param) = @_;

    my @inputs = $cgi->param ($param or 'br');
    croak "No input file given; or input file lost.\n"
	unless @inputs and @inputs > 0;

    my @full_names = ();
    my @base_names = ();

    foreach my $input (@inputs) {
	# because of files with spaces in the name
	# (I thought that this unescape is done already by the CGI...)
	use CGI::Util;
	$input = CGI::Util::unescape ($input);
	$input = "/$input"
	    unless $input =~ m{^/};  # because it will be prepended by $root_data
	push (@full_names, "$root_data$input");
	push (@base_names, $input);
    }

    if (wantarray) {
	return (\@full_names, \@base_names);
    } else {
	return \@full_names;
    }
}

#-----------------------------------------------------------------
# Return a piece of the document 'head' - the style. Use it like this:
#   print $cgi->start_html (-style => Magena::Server::style ($cgi));
# -----------------------------------------------------------------
sub style {
    my $cgi = shift;
    return { src => CFG->get ('web.root.doc', '/magena/doc') . '/css/magena.css' };
}

#-----------------------------------------------------------------
#
# -----------------------------------------------------------------
sub error {
    my ($cgi, $msg) = @_;
    print $cgi->header ('text/html');
    print $cgi->start_html ( -style => style ($cgi) );
    print $cgi->div ({ class => 'error' }, $msg);
}

#-----------------------------------------------------------------
# Create an HTML component: a right-aligned 'div' with an icon and a
# link to be create in a browser's new window. The URL will be deduced
# from the given (current) CGI object. Optional message will be used
# for the 'title' attribute.
#
# It returns an empty and invisible 'div' if there is a CGI parameter
# "nw=no";
# -----------------------------------------------------------------
sub nw_link {
    my ($cgi, $msg) = @_;
    $msg = 'Open result in a new window'
	unless defined $msg;
    my $images = CFG->get ('web.root.doc', '/magena/doc') . '/images';
    my $no_nw = $cgi->param ('nw');
    if ($no_nw and $no_nw eq 'no') {
	return invisible ($cgi);
    }
    return
	$cgi->div ({ align => "right",
		     style => 'float:right' },
		   $cgi->a ({ href   => $cgi->url (-path_info => 1, -query => 1) . ';nw=no',
			      target => '_blank', },
			    $cgi->img ({ src    => "$images/newwin.gif",
					 border => 0,
					 title  => $msg})));
}

sub invisible {
    my $cgi = shift;

#    return $cgi->div ({ style => 'display:none' });
# BUG in browser? if I use $cgi->div(...), it creates <div .../> which
# make invisible also the surrounding div. Therefore, using this:

    return '<div style="display:none"></div>';
}

1;
__END__
