#!/usr/bin/perl
#
# Usage: ./extract_needed_bioperl_modules.pl
#        ./extract_needed_bioperl_modules.pl <dir-where-to-extract>
#
# Comments to Martin Senger <martin.senger@gmail.com>
# -----------------------------------------------------------------

use Modern::Perl;
use lib "/home/senger/bioperl-live";
#use lib "./";

use Commons::Base;
use Commons::Config;
use Commons::Test;
use Commons::Utilities;
use Commons::Version;

use Magena::Annotation;
use Magena::BlastHit;
use Magena::BlastResult;
use Magena::BlastResults;
use Magena::GFF3;
use Magena::Server;
use Magena::Utilities;
use Magena::Version;

use CGI;
use CGI::Carp;
use Template;
use File::Spec;
use Carp;
use JSON;
use Template;
use HTML::Entities ();

use File::Find ();
use File::Path qw( make_path );
use File::Basename;

#say join ("\n", sort keys %INC);
#__END__

my @files = grep { m{Bio/} } values %INC;
foreach my $file (@files) {
    my $dir = $file;
    $dir =~ s{.pm$}{};
    if (-e $dir and -d $dir) {
	File::Find::find ( { wanted => \&wanted }, $dir );
    }
}
sub wanted {
    /^.*\.pm\z/s
    && push (@files, $File::Find::name);
}

my $dest_dir = ($ARGV[0] or './');
die "'$dest_dir' does not seem to exist.\n"
    unless -e $dest_dir;
die "'$dest_dir' does not seem to be a directory.\n"
    unless -d $dest_dir;
$dest_dir .= '/' unless $dest_dir =~ m{/$};

foreach my $file (sort @files) {
    my ($path, $rest) = $file =~ m{^(.*)(Bio/.+)$};
    my ($out_file, $out_path) = fileparse ($rest);
    make_path ("$dest_dir$out_path");
    `cp $file $dest_dir$rest`;
#    say "cp $file $dest_dir$rest";
}
say scalar @files . " files copied into $dest_dir";
