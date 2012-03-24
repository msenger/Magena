#-----------------------------------------------------------------
# Commons::Config
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#
#-----------------------------------------------------------------

package Commons::Config;

use Commons::Version;
our $VERSION = $Commons::Version::VERSION;

use strict;
use warnings;
use feature qw( say );
use Config::Simple;
use File::Spec;
our @ISA = qw( Exporter );
our @EXPORT = qw( $CFG );    # $CFG is a singleton
our $ENV_CONFIG_DIR = 'DEFAULT_COMMONS_CONFIG_DIR';
our $CFG = Commons::Config->_new();

my %Config = ();     # here are all configuration properties
my %Unsuccess = ();  # here are names (and reasons) of failed files
my %Success = ();    # here are names of successfully read files

#-----------------------------------------------------------------
# Able to define configuration propertie and configuration files in
# the "use" pragma (which is identical to calling "add" method later).
# -----------------------------------------------------------------
sub import {
     shift;
     $CFG->add (@_);
     Commons::Config->export_to_level (1);  # let Exporter export
}

#-----------------------------------------------------------------
# Creation of the $CFG singleton. Any next attempt returns the same
# $CFG, but with added arguments given here (@args).
# -----------------------------------------------------------------
sub _new {
    my ($class, @args) = @_;

    # create an object - but only just once
    my $self = $CFG || bless {}, ref ($class) || $class;

    # add arguments to the object
    $self->add (@args);

    # done
    return $self;
}

#-----------------------------------------------------------------
# @configs can be mix of scalars (names of configuration files) and
# hash references (direct configuration parameters); can be called
# more times - everything is appended; the same file is, however, read
# only once
# -----------------------------------------------------------------
sub add {
    shift;    # ignore invocant
    my (@configs) = @_;

    # add parameters AND resolve file names
    foreach my $config (@configs) {
	if (ref ($config) eq 'HASH') {
	    %Config = (%Config, %$config);
	} else {
	    my $file = Commons::Config->_resolve_file ($config);
	    $file = File::Spec->rel2abs ($file) if $file;
	    unless ($file) {
		$Unsuccess{$config} = 'File not found.';
		next;
	    }
	    unless ($Success{$file}) {   # do not re-read the same file
		eval {
		    Config::Simple->import_from ($file, \%Config);
		};
		if ($@) {
		    $@ =~ s| /\S+Config/Simple.pm line \d+, <FH>||;
#		    print STDERR "Reading configuration file '$file' failed: $@\n";
		    $Unsuccess{$file} = $@;
		} else {
		    $Success{$file} = 1;
#		    print STDERR "Using configuration file '$file'\n";
		}
	    }
	}
    }

    # I do not like default.XXX (done by Config::Simple) - so
    # replicate these keys without the prefix 'default'
    foreach my $key (keys %Config) {
	my ($realkey) = ($key =~ /^$Config::Simple::DEFAULTNS\.(.*)/);
	if ($realkey && ! exists $Config{$realkey}) {
	    $Config{$realkey} = $Config{$key};
	}
    }

    # Remove potential whitespaces from the keys (Config::Simple may
    # leave them there)
    map { my $orig_key = $_;
	  s/\s//g and $Config{$_} = delete $Config{$orig_key}  } keys %Config;
}

# -----------------------------------------------------------------
# try to locate given $filename, return its full path:
#  a) as it is - if such file exists
#  b) as $ENV{DEFAULT_CFG_DIR}/$filename
#  c) in one of the @INC directories
#  d) return undef
# -----------------------------------------------------------------
sub _resolve_file {
    shift;    # ignore invocant
    my ($filename) = @_;
    return $filename if -f $filename;

    my $realfilename;
    if ($ENV{$ENV_CONFIG_DIR}) {
	$realfilename = File::Spec->catdir ($ENV{$ENV_CONFIG_DIR}, $filename);
	return $realfilename if -f $realfilename;
    }

    foreach my $prefix (@INC) {
	$realfilename = File::Spec->catfile ($prefix, $filename);
	return $realfilename if -f $realfilename;
    }
    return undef;
}

# -----------------------------------------------------------------
# Get a value of a configuration property.
# -----------------------------------------------------------------
sub get {
    shift;

    # If called with no arguments, return:
    # i) in scalar context: a hashref with all properties
    # ii) in list context: all available keys
    unless (@_) {
	return unless defined wantarray;
	return keys %Config if wantarray;
	return wantarray ? keys %Config : \%Config;
    }

    # if called with a single argument, return the value
    # matching this key; otherwise return a default value
    # (which is in the second argument, and may be undef)
    my ($key, $default_value) = @_;
    my $value = $Config{$key};
    return $value if defined $value;
    return $default_value;
}

# -----------------------------------------------------------------
# Set a value of a configuration property.
# -----------------------------------------------------------------
sub set {
    shift;

    # If called with no arguments, do nothing
    return unless @_;

    # more arguments means adding...
    return $Config{$_[0]} = $_[1];
}

# -----------------------------------------------------------------
# Remove one, more, or all configuration properties.
# -----------------------------------------------------------------
sub delete {
    shift;

    # if called with no arguments, delete all keys
    %Config = () and return unless @_;

    # if called with arguments, delete the matching keys
    foreach my $key (@_) {
	delete $Config{$key};
    }
}

# -----------------------------------------------------------------
# Return a stringified version of all configuration options; an
# optional argument is a name for variable into which it is
# stringified (I do not know how to express it better: simply speaking
# this argument is passed to the Data::Dumper->Dump as the variable
# name)
# -----------------------------------------------------------------
sub dump {
    shift;
    my $varname = @_ ? shift : 'CONFIG';
    require Data::Dumper;
    return Data::Dumper->Dump ( [\%Config], [$varname]);
}

# -----------------------------------------------------------------
# Return a list of configuration files successfully read (so far)
# -----------------------------------------------------------------
sub ok_files {
    return sort keys %Success;
}

# -----------------------------------------------------------------
# Return a refhash of configuration files un-successfully read (so far) -
# with corresponding error messages
# -----------------------------------------------------------------
sub failed_files {
    return \%Unsuccess;
}

# -----------------------------------------------------------------
# see docs in Config.java (TBD)
# without $prefix, it returns an empty hashref
# -----------------------------------------------------------------
sub matching {
    my ($self, $prefix) = @_;
    return {} unless $prefix;
    my %result;
    my @wanted_keys = grep { /^\Q$prefix\E/ } keys %Config;
    my @new_keys = map { /^\Q$prefix\E\.?(.*)/ } @wanted_keys;
    @result{@new_keys} = @Config{@wanted_keys};
    return \%result;
}

# -----------------------------------------------------------------
# Prefix is the value asssociated to the $key
# -----------------------------------------------------------------
sub matching_by_key {
    my ($self, $key) = @_;
    return {} unless $key;
    return $self->matching ($self->get ($key));
}

# -----------------------------------------------------------------
# Return a human-readable report obout the current
# configuration. $prefix can select only some properties to be
# included in the report.
# -----------------------------------------------------------------
sub report {
    my ($self, $prefix) = @_;
    my $doc = '';

    $doc .= 'Configuration' . "\n";
    $doc .= '-------------' . "\n";

    $doc .= "Environment variable $ENV_CONFIG_DIR" .
	( exists $ENV{$ENV_CONFIG_DIR} ? ": $ENV{$ENV_CONFIG_DIR}" : ' is not set') . "\n";

    my @ok = $self->ok_files();
    if (@ok == 0) {
	$doc .= 'No configuration file in use' . "\n";
    } else {
	$doc .= 'Successfully read configuration files:' . "\n";
	foreach my $file (@ok) {
	    $doc .= "\t$file" . "\n";
	}
    }

    my $failed = $self->failed_files();
    if (keys %$failed > 0) {
	$doc .= 'Failed configuration file(s):' . "\n";
	foreach my $file (sort keys %$failed) {
	    my $msg = $failed->{$file}; $msg =~ s/\n$//;
	    $doc .= "\t$file => $msg" . "\n";
	}
    }

    my @keys = $self->get();
    if (@keys == 0) {
	$doc .= 'No configuration properties exist' . "\n";
    } elsif ($prefix) {
	$doc .= "Configuration properties '$prefix':" . "\n";
	map { $doc .= _print_prop ($_, $self->get ($_)) } sort grep { /^\Q$prefix\E/ } @keys;
    } else {
	$doc .= 'All configuration properties:';
	map { $doc .= _print_prop ($_, $self->get ($_)) } sort @keys;
    }
    sub _print_prop {
	my ($key, $value) = @_;
	return "\t$key => " . ($value ? $value : '') . "\n";
    }
    return $doc;
}

1;
__END__
