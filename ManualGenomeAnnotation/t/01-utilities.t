#!perl -w

#use Test::More qw(no_plan);
use Test::More tests => 83;
use File::Temp;
use Commons::Test;

ok(1);
use Magena::Utilities;
diag( "Testing Magena::Utilities" );

#
# _files
#
do_files (undef, ['b'], ['b']);
do_files ('a', undef, ['a']);
do_files (undef, undef, []);
do_files ('', ['b'], ['b']);
do_files ('a', [], ['a']);
do_files ('', [], []);
do_files ('a', ['b'], ['a', 'b']);
do_files ('a', ['b', 'c'], ['a', 'b', 'c']);

sub do_files {
    my ($file, $files, $expected) = @_;
    my $dummy = Magena::Utilities::blastsplit->new (
	file => $file, files => $files
	);
    my @merged = Magena::Utilities->_files ($dummy);
    is (@merged+0, @$expected+0, "_files: Number of files");
    ok (Commons::Test::compare_arrays ($expected, \@merged), "_files: Compare arrays");
}

#
# split blast results
#
our $input = Commons::Test::test_file ('blast_results.bls');
ok (-e $input, "Getting testing input") or
    diag ("File '$input' cannot be found");
{
    local *FILE;
    ok (open (FILE, $input), "Opening $input");
    local $/ = undef;
    our $input_contents .= <FILE>;
    close (FILE);
}

do_split (  0, 1);
do_split (  1, 5);
do_split (  2, 3);
do_split (  3, 2);
do_split (  4, 2);
do_split (  5, 1);
do_split (  6, 1);
do_split (  7, 1);
do_split ( -1, 1);

# $max ... maximum results in one file
# $expected ... how many files is expected
sub do_split {
    my ($max, $expected) = @_;
    my $dir = File::Temp->newdir (CLEANUP => 1);
#    diag ("Temp directory is $dir");
    my $outputs = Magena::Utilities->split_blast_results (file       => $input,
							  output_dir => $dir,
							  max        => $max,
	);
    is (@$outputs+0, $expected, "Return value");
    ok (opendir (DIR, $dir), "Opening $dir");
    my @parts =	sort map { File::Spec->catfile ($dir, $_) } grep { /\.\d{5}/ } readdir DIR;
    closedir DIR;
#    diag ("Created " . (@parts+0) . " parts");
    is (@parts+0, $expected, "$expected output file(s)");

    my $document = '';
    foreach my $file (@parts) {
#    	diag ("File: $file");
    	ok (open (FILE, $file), "Opening $file");
    	local $/ = undef;
    	$document .= <FILE>;
    	close (FILE);
    }
    is (length $document, length $input_contents,
    	"Comparing file lengths for MAX=$max and EXPECTED=$expected");
    ok ($document eq $input_contents,
    	"Comparing files for MAX=$max and EXPECTED=$expected");
}

# testing error conditions
eval { Magena::Utilities->split_blast_results() };
ok ($@ =~ /Missing argument/, "Missing input file error");

eval { Magena::Utilities->split_blast_results ( file       => $input,
						output_dir => $input ) };
ok ($@ =~ /Cannot use/, "Equal input and output error");

__END__
