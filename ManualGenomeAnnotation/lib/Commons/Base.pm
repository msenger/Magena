#-----------------------------------------------------------------
# Commons::Base
# Author: Martin Senger <martin.senger@gmail.com>
# For copyright and disclaimer see below.
#-----------------------------------------------------------------

package Commons::Base;
use strict;
use warnings;
use Carp;

use Commons::Version;
our $VERSION = $Commons::Version::VERSION;

use Commons::Utilities;
use vars qw( $AUTOLOAD );
our @ISA = qw( Exporter );
our @EXPORT = qw( $CFG CFG LOG );

# names of attribute's types
use constant STRING   => 'string';
use constant INTEGER  => 'integer';
use constant FLOAT    => 'float';
use constant BOOLEAN  => 'boolean';
use constant DATETIME => 'datetime';

# names of attribute's properties
use constant TYPE     => 'type';
use constant POST     => 'post';
use constant ISARRAY  => 'is_array';
use constant READONLY => 'readonly';

use overload q("") => "as_string";

# initiate error handling
use Carp qw( confess );

# initiate configuration
use Commons::Config;
sub CFG { return $CFG }

#-----------------------------------------------------------------
# Expected arguments:
#
# 1) any hash is passed to Commons::Config (it contains configuration
# properties)
#
# 2) any -cfg=\w+ is passed also to Commons::Config (it should be a
# name of a configuration file); the same for the pair of arguments
# "-cfg" and "\w+"
#
# 3) -logname=\w+ is used as a default logger name (see Log4perl); the
# same for the similar pair of arguments
#
# 4) -nodefaultcfg and -forcedefaultcfg (case insensitive) are removed
# and used here (to deal with a default configuration file)
#
# Everything else is passed to Exporter as things to be exported.
# -----------------------------------------------------------------
our %IMPORT_CALLED;
sub import {
    my ($self, @args) = @_;
    my $caller_pkg = caller();
    return 1 if $IMPORT_CALLED{$caller_pkg}++;

    my $args = _parse_import_args ($caller_pkg, @args);
    # use Data::Dumper;
    # print STDERR "$caller_pkg: " . Dumper ($args->{cfg_args});

    $CFG->add (@{ $args->{cfg_args} });
    _init_logging ($args->{logging});

    # TBD: This works for this class but not for sub-classes; why?
    Commons::Base->export_to_level (1, @{ $args->{'export_args'} });
}

# return a hash with keys 'cfg_args', 'export_args' and
# 'logging_args', each of them with a refarray or a scalar value with
# corresponding contents;

our $Nocfg = 0; # must be global for repetitive import calls

sub _parse_import_args {
    my ($caller_pkg, @args) = @_;
    my @cfg_args = ();
    my ($forcecfg, $cfgdefined) = (0,0);
    my @exp_args = ();
    my $logname;
    for (my $i = 0; $i < @args; $i++) {
	my $arg = $args[$i];
	if (ref ($arg) eq 'HASH') {
	    push (@cfg_args, $arg);
	} elsif ($arg =~ /-cfg=(.*+)/i) {
	    push (@cfg_args, $1);
	    $cfgdefined = 1;
	} elsif ($arg eq '-cfg' and $i < $#args) {
	    push (@cfg_args, $args[++$i]);
	    $cfgdefined = 1;
	} elsif ($arg =~ /^-forcedefaultcfg$/i) {
	    $forcecfg = 1;
	} elsif ($arg =~ /^-nodefaultcfg$/i) {
	    $Nocfg = 1;
	} elsif ($arg =~ /^-logname=(.*+)$/i) {
	    $logname = $1;
	} elsif ($arg eq '-logname' and $i < $#args) {
	    $logname = $args[++$i];
	} else {
	    push (@exp_args, $arg);
	}
    }
# a cfg file  -force         -no                         bit
# defined      defaultcfg     defaultcfg                 mask
# -----------------------------------------------------------
#    1            1            0       add default config  6
#    1            0            1       no default config   5
#    1            0            0       no default config   4
#    1            1            1       add default config  7

#    0            1            0       add default config  2
#    0            0            1       no default config   1
#    0            0            0       add default config  0
#    0            1            1       add default config  3
    my $mask = $cfgdefined * 4 + $forcecfg * 2 + $Nocfg;
#    print STDERR "MASK-1: $mask, $cfgdefined, $forcecfg, $Nocfg\n";
    if ($mask =~ /7|6|3|2|0/) {
	my $default_cfg = Commons::Utilities::find_file_by_module ($caller_pkg, '.cfg');
	unshift (@cfg_args, $default_cfg) if $default_cfg;
    }
    return { cfg_args    => \@cfg_args,
	     export_args => \@exp_args,
	     logging     => $logname };
}

#-----------------------------------------------------------------
# Initiate logging.
#
# It looks for logging configuration (file) here:
#
# 1) Property 'log.config' (or 'web.log.config' if in the CGI
# environament) in the normal (not logging) configuration file. If
# this property exists but its value is not an existing file, it stops
# looking for a file and go directly to the fallback (see below).
#
# 2) Try to find "default.log4p.properties" file. If this is used, the
# given $logname is not used - because this default configuration is
# configured only for rootLogger.
#
# 3) [fallback] Create programatically some logging properties. 
# -----------------------------------------------------------------
use Log::Log4perl qw(get_logger :levels :no_extra_logdie_message);
sub _init_logging {
    my $logname = shift;
    my $log_ok;
    my $log_config_property;
    my $log_config_default_value;
    if ($ENV{GATEWAY_INTERFACE} && $ENV{GATEWAY_INTERFACE} =~ /CGI/i) {
	$log_config_property = 'web.log.config';
	$log_config_default_value = 'web.default.log4p.properties';
    } else {
	$log_config_property = 'log.config';
	$log_config_default_value = 'default.log4p.properties';
    }
    my $log_config = $CFG->get ($log_config_property);
    if ($log_config) {
	$log_ok = (_load_log_config ($log_config) or
		   _load_log_config (Commons::Utilities::find_file_in_INC ($log_config_default_value)));
    } else {
	$log_ok = _load_log_config (Commons::Utilities::find_file_in_INC ($log_config_default_value));
    }
    unless ($log_ok) {
	# configuration for logging was not found; make some easy logging
	my $logfile = $CFG->get ('log.file');
	my $loglevel = $CFG->get ('log.level', $ERROR);
	my $pattern = $CFG->get ('log.pattern', '%d (%r) %p> [%x] %F{1}:%L - %m%n');
	my $log = get_logger ($logname or '');
	$log->level (uc $loglevel);
	my $appender =
	    ($logfile and $logfile !~ /^stderr$/i) ?
	    Log::Log4perl::Appender->new (
		"Log::Log4perl::Appender::File",
		name     => 'Log',
		filename => $logfile,
		mode     => 'append') :
		Log::Log4perl::Appender->new ("Log::Log4perl::Appender::Screen",
					      name     => 'Screen');
	$log->add_appender ($appender);
	my $layout = Log::Log4perl::Layout::PatternLayout->new ($pattern);
	$appender->layout ($layout);
    }
}

sub _load_log_config {
    my $log_config = shift;
    return 0 unless $log_config;
    eval { Log::Log4perl->init ($log_config) };
    return 1 unless $@;
#    warn "Problem loading logging configuration file '$log_config': $@\n";
    return 0;
}
# public (exported) method
sub LOG { return get_logger (@_) }


#-----------------------------------------------------------------
# These methods are called by set/get methods of the sub-classes. If
# it comes here, it indicates that an attribute being get/set does not
# exist.
#-----------------------------------------------------------------

{
    my %_allowed =
	(
	 );

    sub _accessible {
	my ($self, $attr) = @_;
	exists $_allowed{$attr};
    }
    sub _attr_prop {
	my ($self, $attr_name, $prop_name) = @_;
	my $attr = $_allowed {$attr_name};
	return ref ($attr) ? $attr->{$prop_name} : $attr if $attr;
	return undef;
    }
}

#-----------------------------------------------------------------
# new
#-----------------------------------------------------------------
sub new {
    my ($class, @args) = @_;
#    LOG->debug ("NEW: $class - " . join (", ", @args)) if LOG->is_debug;

    # create an object
    my $self = bless {}, ref ($class) || $class;

    # initialize the object
    $self->init();

    # set all @args into this object with 'set' values
    my (%args) = (@args == 1 ? (value => $args[0]) : @args);
    foreach my $key (keys %args) {
        no strict 'refs'; 
        $self->$key ($args {$key});
    }

    # done
    return $self;
}

#-----------------------------------------------------------------
# init
#-----------------------------------------------------------------
sub init {
    my ($self) = shift;
}

#-----------------------------------------------------------------
#
#  Error handling
#
#-----------------------------------------------------------------

my $DEFAULT_THROW_WITH_LOG   = 0;
my $DEFAULT_THROW_WITH_STACK = 1;

#-----------------------------------------------------------------
# throw
#-----------------------------------------------------------------
sub throw {
   my ($self, $msg) = @_;
   $msg .= "\n" unless $msg =~ /\n$/;

   # make an instance, if called as a class method
   unless (ref $self) {
       no strict 'refs'; 
       $self = $self->new;
   }

   # add (optionally) stack trace
   $msg ||= 'An error.';
   my $with_stack = (defined $self->enable_throw_with_stack ?
		     $self->enable_throw_with_stack :
		     $DEFAULT_THROW_WITH_STACK);
   my $result = ($with_stack ? $self->format_stack ($msg) : $msg);

   # die or log and die?
   my $with_log = (defined $self->enable_throw_with_log ?
		   $self->enable_throw_with_log :
		   $DEFAULT_THROW_WITH_LOG);
   if ($with_log) {
       LOG->logdie ($result);
   } else {
       croak ($result);
   }
}

#-----------------------------------------------------------------
# Some throwing options
#
#    These options are not set by using AUTOLOAD (as other regular
#    attributes) because AUTOLOAD could raise exception and we would be
#    in a deep..., well deep recursion.
#
#    Default values are: NO  enable_throw_with_log
#                        YES enable_throw_with_stack
#    (but they are globally changeable by calling
#     default_throw_with_log and default_throw_with_stack)
#
#-----------------------------------------------------------------
sub enable_throw_with_log {
    my ($self, $value) = @_;
    $self->{enable_throw_with_log} = ($value ? 1 : 0)
	if (defined $value);
    return $self->{enable_throw_with_log};
}

sub default_throw_with_log {
    my ($self, $value) = @_;
    $DEFAULT_THROW_WITH_LOG = ($value ? 1 : 0)
	if defined $value;
    return $DEFAULT_THROW_WITH_LOG;
}

sub enable_throw_with_stack {
    my ($self, $value) = @_;
    $self->{enable_throw_with_stack} = ($value ? 1 : 0)
	if defined $value;
    return $self->{enable_throw_with_stack};
}

sub default_throw_with_stack {
    my ($self, $value) = @_;
    $DEFAULT_THROW_WITH_STACK = ($value ? 1 : 0)
	if defined $value;
    return $DEFAULT_THROW_WITH_STACK;
}

#-----------------------------------------------------------------
# format_stack
#-----------------------------------------------------------------
sub format_stack {
    my ($self, $msg) = @_;
    my $stack = $self->_reformat_stacktrace ($msg);
    my $class = ref ($self) || $self;

    my $title = "------------- EXCEPTION: $class -------------";
    my $footer = "\n" . '-' x CORE::length ($title);
    return "\n$title\nMSG: $msg\n" . $stack . $footer . "\n";
}


#-----------------------------------------------------------------
# _reformat_stacktrace
#    Taken from bioperl.
#
#  Takes one argument - an error message. It uses it to remove its
#  repeated occurences from each line (not to print it).
#
#  Reformatting of the stack:
#    1. Shift the file:line data in line i to line i+1.
#    2. change xxx::__ANON__() to "try{} block"
#    3. skip the "require" and "Error::subs::try" stack entries (boring)
#  This means that the first line in the stack won't have
#  any file:line data.
#-----------------------------------------------------------------
sub _reformat_stacktrace {
    my ($self, $msg) = @_;
    my $stack = Carp->longmess;
    $stack =~ s/\Q$msg//;
    my @stack = split( /\n/, $stack);
    my @new_stack = ();
    my ($method, $file, $linenum, $prev_file, $prev_linenum);
    my $stack_count = 0;
    foreach my $i ( 0..$#stack ) {
        if ( ($stack[$i] =~ /^\s*([^(]+)\s*\(.*\) called at (\S+) line (\d+)/) ||
	      ($stack[$i] =~ /^\s*(require 0) called at (\S+) line (\d+)/) ) {
            ($method, $file, $linenum) = ($1, $2, $3);
            $stack_count++;
        } else {
            next;
        }
        if( $stack_count == 1 ) {
            push @new_stack, "STACK: $method";
            ($prev_file, $prev_linenum) = ($file, $linenum);
            next;
        }

        if( $method =~ /__ANON__/ ) {
            $method = "try{} block";
        }
        if( ($method =~ /^require/ and $file =~ /Error\.pm/ ) ||
            ($method =~ /^Error::subs::try/ ) )   {
            last;
        }
        push @new_stack, "STACK: $method $prev_file:$prev_linenum";
        ($prev_file, $prev_linenum) = ($file, $linenum);
    }
    push @new_stack, "STACK: $prev_file:$prev_linenum";

    return join "\n", @new_stack;
}

#-----------------------------------------------------------------
# Set methods test whether incoming value is of a correct type.
# Here we return message explaining that it isn't.
#-----------------------------------------------------------------
sub _wrong_type_msg {
    my ($self, $given_type_or_value, $expected_type, $method) = @_;
    my $msg = 'In method ';
    if (defined $method) {
	$msg .= $method;
    } else {
	$msg .= (caller(1))[3];
    }
    return ("$msg: Trying to set '$given_type_or_value' but '$expected_type' is expected.");
}

#-----------------------------------------------------------------
# Deal with 'set', 'get' and 'add_' methods.
#-----------------------------------------------------------------
sub AUTOLOAD {
    my ($self, @new_values) = @_;
    my $ref_sub;
    if ($AUTOLOAD =~ /.*::(\w+)/ && $self->_accessible ("$1")) {

	# get/set method
	my $attr_name = "$1";
	my $attr_type = $self->_attr_prop ($attr_name, TYPE) || STRING;
	my $attr_post = $self->_attr_prop ($attr_name, POST);
	my $attr_is_array = $self->_attr_prop ($attr_name, ISARRAY);
	my $attr_readonly = $self->_attr_prop ($attr_name, READONLY);
	$ref_sub =
	    sub {
		local *__ANON__ = "__ANON__$attr_name" . "_" . ref ($self);
		my ($this, @values) = @_;
		return $this->_getter ($attr_name) unless @values;
		$self->throw ("Sorry, the attribute '$attr_name' is read-only.")
		    if $attr_readonly;

		# here we continue with 'set' method:
		if ($attr_is_array) {
		    my @result = (ref ($values[0]) eq 'ARRAY' ? @{$values[0]} : @values);
		    foreach my $value (@result) {
			$value = $this->check_type ($AUTOLOAD, $attr_type, $value);
		    }
		    $this->_setter ($attr_name, $attr_type, \@result);
		} else {
		    $this->_setter ($attr_name, $attr_type, $this->check_type ($AUTOLOAD, $attr_type, @values));
		}

		# call post-procesing (if defined)
		$this->$attr_post ($this->{$attr_name}) if $attr_post;

		return $this->{$attr_name};
	    };

    } elsif ($AUTOLOAD =~ /.*::add_(\w+)/ && $self->_accessible ("$1")) {

	# add_XXXX method
	my $attr_name = "$1";
	if ($self->_attr_prop ($attr_name, ISARRAY)) {
	    my $attr_type = $self->_attr_prop ($attr_name, TYPE) || STRING;
	    $ref_sub =
		sub {
		    local *__ANON__ = "__ANON__$attr_name" . "_" . ref ($self);
		    my ($this, @values) = @_;
		    if (@values) {
			my @result = (ref ($values[0]) eq 'ARRAY' ? @{$values[0]} : @values);
			foreach my $value (@result) {
			    $value = $this->check_type ($AUTOLOAD, $attr_type, $value);
			}
			$this->_adder ($attr_name, $attr_type, @result);
		    }
		    return $this;
		}
	} else {
	    $self->throw ("Method '$AUTOLOAD' is allowed only for array-type attributes.");
	}

    } else {
	$self->throw ("No such method: $AUTOLOAD");
    }

    no strict 'refs'; 
    *{$AUTOLOAD} = $ref_sub;
    use strict 'refs'; 
    return $ref_sub->($self, @new_values);
}

#-----------------------------------------------------------------
# The low level get/set methods. They are called from AUTOLOAD, and
# they are separated here so they can be overriten. Also, there may
# be situation that one can call them if other features (such as
# type checking) are not requiered.
# -----------------------------------------------------------------
sub _getter {
    my ($self, $attr_name) = @_;
    return $self->{$attr_name};
}

sub _setter {
    my ($self, $attr_name, $attr_type, $value) = @_;
    $self->{$attr_name} = $value;
}

sub _adder {
    my ($self, $attr_name, $attr_type, @values) = @_;
    push ( @{ $self->{$attr_name} }, @values );
}

#-----------------------------------------------------------------
# Keep it here! The reason is the existence of AUTOLOAD...
#-----------------------------------------------------------------
sub DESTROY {
}

#-----------------------------------------------------------------
#
# Check type of @value against $expected_type. Return checked $value
# (perhaps trimmed, or otherwise corrected - e.g. wrapped in an
# appropriate object), or undef if the $value is of a wrong type or if
# the $value itself is undef. In case of a wrong type, it throws an
# exception. See more about @values in the comments below.
#
#-----------------------------------------------------------------
sub check_type {
    my ($self, $name, $expected_type, @values) = @_;
    my $value = $values[0];

    return undef unless defined $value;

    # process cases when an expected type is a simple data type
    # (string, integer etc.)

    if ($expected_type eq STRING) {
	return $value;

    } elsif ($expected_type eq INTEGER) {
	$self->throw ($self->_wrong_type_msg ($value, $expected_type, $name))
	    unless $value =~ m/^\s*[+-]?\s*\d+\s*$/;
	$value =~ s/\s//g;
	return $value;

    } elsif ($expected_type eq FLOAT) {
	$self->throw ($self->_wrong_type_msg ($value, $expected_type, $name))
	    unless $value =~ m/^\s*[+-]?\s*(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?\s*$/;
	$value =~ s/\s//g;
	return $value;

    } elsif ($expected_type eq BOOLEAN) {
	return ($value =~ /true|\+|1|yes|ano/ ? '1' : '0');

    } elsif ($expected_type eq DATETIME) {
	my $iso;
	eval { 
	    $iso = (HTTP::Date::time2isoz (HTTP::Date::str2time (HTTP::Date::parse_date ($value))));
	};
	$self->throw ($self->_wrong_type_msg ($value, 'ISO-8601', $name))
	    if $@;
	return $iso;   ### $iso =~ s/ /T/;  ??? TBD

    } else {

	# Then process cases when the expected type is a name of a
	# real object (e.g. Magena::BlastHit); for these cases
	# the $value[0] can be already such object - in which case
	# nothing to be done; or $value[0] can be HASH, or @values can
	# be a list of name/value pairs, in which case a new object
	# (of type $expected_type) has to be created and initialized
	# by @values; and, still in the latter case, if the @values
	# has just one element (XX), this element is considered a
	# 'value': it is treated as a a hash {value => XX}.

	return $value if UNIVERSAL::isa ($value, $expected_type);

	$value = { value => $value }
	    unless ref ($value) || @values > 1;

	my ($value_ref_type) = ref ($value);
	if ($value_ref_type eq 'HASH') {
	    # e.g. $sequence->Length ( { value => 12, id => 'IR64'} )
	    return $self->create_member ($name, $expected_type, %$value);

	} elsif ($value_ref_type eq 'ARRAY') {
	    # e.g. $sequence->Length ( [ value => 12, id => 'IR64'] )
	    return $self->create_member ($name, $expected_type, @$value);

	} elsif ($value_ref_type) {
	    # e.g. $sequence->Length ( new Magena::BlastHit ( id => '12') )
	    $self->throw ($self->_wrong_type_msg ($value_ref_type, $expected_type, $name))
		unless UNIVERSAL::isa ($value, $expected_type);
	    return $value;

	} else {
	    # e.g. $sequence->Length (value => 12, id => 'IR64')
	    return $self->create_member ($name, $expected_type, @values);

	}
    }
}

#-----------------------------------------------------------------
#
#-----------------------------------------------------------------
sub create_member {
    my ($self, $name, $expected_type, @values) = @_;
    eval "require $expected_type";
    $self->throw ($self->_wrong_type_msg ($values[0], $expected_type, $name))
	if $@;
    return "$expected_type"->new (@values);
}

#-----------------------------------------------------------------
# as_string (an "" operator overloading)
#-----------------------------------------------------------------
my $DUMPER;
BEGIN {
    use Dumpvalue;
    use IO::Scalar;
    $DUMPER = Dumpvalue->new();
#    $DUMPER->set (veryCompact => 1);
}
sub as_string {
    my $self = shift;
    my $dump_str;
    my $io = IO::Scalar->new (\$dump_str);
    my $oio = select ($io);
    {
	local $self->{parent_obj} = undef;   # TBD: configurable?
	$DUMPER->dumpValue (\$self);
    }
    select ($oio);
    return $dump_str;
}

1;
__END__
