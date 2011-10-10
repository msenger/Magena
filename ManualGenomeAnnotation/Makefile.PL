use strict;
use warnings;
use ExtUtils::MakeMaker;

WriteMakefile(
    NAME                => 'Magena',
    AUTHOR              => q{Martin Senger <martin.senger@gmail.com>},
    VERSION_FROM        => 'lib/Magena/Version.pm',
    ABSTRACT_FROM       => 'lib/Magena.pod',
#    ($ExtUtils::MakeMaker::VERSION >= 6.3002
#      ? ('LICENSE'=> 'perl')
#      : ()),
    EXE_FILES           => ['scripts/magena'],
    PL_FILES            => {},
    PREREQ_PM => {
	'Config::Simple' => 0,
	'Error'          => 0,
	'HTML::Entities' => 0,
	'HTML::Parser'   => 0,
	'HTTP::Date'     => 0,
	'HTTP::Headers'  => 0,
	'HTTP::Message'  => 0,
	'HTTP::Request'  => 0,
	'HTTP::Response' => 0,
	'HTTP::Status'   => 0,
	'IO::Scalar'     => 0,
	'IO::WrapTie'    => 0,
	'JSON'           => 0,
	'LWP'            => 0,
	'Log::Log4perl'  => 0,
	'Template'       => 0,
	'URI'            => 0,
        'MRS::Client'    => 0,
#	'Bio::SearchIO' => 'v1.6.9',
    },
    dist                => { COMPRESS => 'gzip -9f', SUFFIX => 'gz', },
    clean               => { FILES => 'Magena-*' },
);
