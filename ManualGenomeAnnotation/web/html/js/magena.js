// some globals (sorry...)
var br_qids = new Array();
var panels = {};

// remove trailing slash from the current URL
if (location.href.charAt (location.href.length - 1) == '/') {
   location.href = location.href.substr (0, location.href.length - 1);
} 

function rememberPanel (panel) {
   panels [panel.prefix] = panel;
}
function findPanel (name) {
   return panels[name];
}

// code that should be executed once the DOM is ready to be traversed
// and manipulated
$(document).ready (function(){

   // Global Ajax setting
   $.ajaxSetup({
      global: false,
      type: "POST",
      traditional: true,
      cache: false,
      timeout: 60000    // 60 secs
   });

});  // end of ready()

// concatenation for the typical jQuery selector
function jid (name) { return '#' + name; }

//
// an object representing a panel for annotations from a particular
// tool (such as blast); all parts of such panels will have IDs
// starting with the given 'prefix'
//
function Panel (prefix) {
   this.prefix = prefix;
   this.main = $(jid (prefix + '-panel'));
   this.triangle = $(jid (prefix + '-triangle'));
   this.loading = $(jid (prefix + '-loading'));
   this.loaded = $(jid (prefix + '-loaded'));
   this.data = {};
   this.url = 'ajax/' + prefix + '-panel';

   this.empty = true;
   this.saved = true;

   // do something with data received from Ajax call
   this.process = function (data) {
      this.main.html (data);
   }

   return this;
}

//
function fillInputsIntoPanel (panel) {
   var inputfiles = $.map ($('.input-file'), function (n, i) {
      return (n.value);
   });
   panel.data.br = (inputfiles.length > 0 ? inputfiles : '');
}

// return false if the panel cannot be loaded (because there are not
// enough input data - not because there was an error during
// loading)
function loadPanel (panel) {

   // tell panel what input to use (it is done only before loading
   // and the same input stays in this panel until next loading)
   fillInputsIntoPanel (panel);

   if ( panel.data.br.length == 0 ) {
      // no input file given
      var dname = '#dialog-message-no-input';
      $(dname).dialog ({
	 modal: true,
	 resizable: false,
	 buttons: {
	    Ok: function() {
		  $(this).dialog('close');
	    }
	 }
      });
      return false;
   }
   panel.empty = true;
   createPanelByAjax (panel);
   return true;
}

//
function showPanel (panel) {
   if ($.isArray (panel.main)) {
      for (var item in panel.main) {
	 panel.main[item].show();
      }
   } else {
      panel.main.show();
   }
   if (panel.triangle) {
      var src = panel.triangle.attr ("src");
      panel.triangle.attr ("src", src.replace ('closed', 'open'));
   }
}

//
function hidePanel (panel) {
   if ($.isArray (panel.main)) {
      for (var item in panel.main) {
	 panel.main[item].hide();
      }
   } else {
      panel.main.hide();
   }
   if (panel.triangle) {
      var src = panel.triangle.attr ("src");
      panel.triangle.attr ("src", src.replace ('open', 'closed'));
   }
}

//
function togglePanel (panel) {
   if (panel.empty) {
      if (loadPanel (panel)) {
	 showPanel (panel);
      }
   } else {
      panel.main.is (':hidden') ? showPanel (panel) : hidePanel (panel);
   }
}

//
function expandPanel (panel) {
   if (panel.empty) {
      if (loadPanel (panel)) {
	 showPanel (panel);
      }
   } else {
      showPanel (panel);
   }
}

//
function startPanel (panel) {
   if (panel.empty || panel.saved) {
      if (loadPanel (panel)) {
	 showPanel (panel);
      }
   } else {
      showPanel (panel);
      confirm ('dialog-confirm-unsaved',
	       function() {
   		  if (loadPanel (panel)) {
   		     showPanel (panel);
		  }
	       },
	       function() {
	       });
   }
}

// create and open a confirmation dialog
// TBD: it does not return anything meaningful
function confirm (dialog_id, yes, no) {
   var dname = '#' + dialog_id;
   $(dname).dialog ({
      resizable: false,
      modal: true,
      buttons: {
	    'Yes': function() { $(this).dialog('close'); yes() },
	    'No' : function() { $(this).dialog('close'); no()  }
      }
      });
}

// if any of the panels is not empty and not saved,
// get the confirmation first, then follow 'url'
function confirmAndGo (url) {
   for (var key in panels) {
      if (panels[key].empty || panels[key].saved) {
	 continue;
      }
      confirm ('dialog-confirm-unsaved',
	       function() {
		  window.location = url;
	       },
	       function() {
	       });
      return;
   }
   window.location = url;
}

// call Ajax to get and set a panel
function createPanelByAjax (panel) {
   $.ajax ({
      context: panel,
      url: panel.url,
      data: panel.data,
      success: function (data) {
	 if ($.isArray (this.main)) {
	    for (var item in this.main) {
	       this.main[item].removeClass ('error');
	    }
	 } else {
	    this.main.removeClass ('error');
	 }
	 panel.process (data);
         this.empty = false;
         this.saved = true;
	 if (this.loaded) {
	    if (this.data.br.length > 0) {
	       var text = this.data.br.join (',');
	       if (text.length > 100) {
		  text = text.substring (0,100) + '<b>...</b>';
	       }
	       this.loaded.html (text);
	    } else {
	       this.loaded.html ('');
	    }
	    this.loaded.show();
	 }
      },
      error: function (xhr, textStatus, errorThrown) {
	 var msg;
	 if (xhr.responseText) {
	    msg = xhr.responseText;
	 } else {
	    msg = 'ERROR: ' + textStatus + ' - ' + ( errorThrown ? errorThrown : xhr.status );
	 }
	 if ($.isArray (this.main)) {
	    for (var item in this.main) {
	       this.main[item].html (msg);
	       this.main[item].removeClass ('error').addClass ('error');
	    }
	 } else {
	    this.main.html (msg);
	    this.main.removeClass ('error').addClass ('error');
	 }
         this.empty = false;
         this.saved = true;
      },
      beforeSend: function (event){
	 if (this.loaded) {
	    this.loaded.hide();
	 }
	 if (this.loading) {
	    this.loading.show();
	 }
      },
      complete: function (xhr, textStatus) {
	 if (this.loading) {
	    this.loading.hide();
	 }
      }
   });
}

function SubPanel (prefix, suffix) {
   this.main = $(jid (prefix + '-panel-' + suffix));
   this.triangle = $(jid (prefix + '-triangle-' + suffix));
   this.loading = $(jid (prefix + '-loading-' + suffix));
   this.data = {};
   this.url = 'ajax/' + prefix + '-subpanel';
   this.empty = true;

   this.process = function (data) {
      this.main.html (data);
   }

   return this;
}

function toggleSubPanel (panel) {
   if (panel.empty) {
      createPanelByAjax (panel);
      showPanel (panel);
   } else {
      if ($.isArray (panel.main)) {
	 panel.main[0].is (':hidden') ? showPanel (panel) : hidePanel (panel);
      } else {
	 panel.main.is (':hidden') ? showPanel (panel) : hidePanel (panel);
      }
   }
}

//
function copy (from_elem_name, to_elem_name) {
   var source = $(jid (from_elem_name));
   var target = $(jid (to_elem_name));
   target.val (source.text());
   target.trigger ('change');
   target.trigger ('set');
}
//
function copy2 (from_elem_value, to_elem_name) {
   var target = $(jid (to_elem_name));
   target.text (from_elem_value);
}

// return 'str' left-padded by zeros to length 2
function pad2 (str) {
   var str = str + '';
   if (str.length == 2) return str;
   if (str.length == 1) return '0' + str;
   return '00';
}

// 'toShow': false => save GFF,
//           true (default) => show GFF (in a modal window)
function saveBr (panel, toShow) {
   if (panel.empty)
      return;

   // fill data that will go back to the server
   var action = (toShow ? 'show' : 'save');
   var modified;
   if ($('#cb-time').attr ('checked') || ! $('#modified').val()) {
      var d = new Date();  // today's date
      modified = d.getFullYear() + '-' + pad2 (d.getMonth()+1) + '-' + pad2 (d.getDate());
   } else {
      modified = $('#modified').val();
   }
   var outputname;
   if ($('#cb-output').attr ('checked')) {
      if (panel.data.br.length > 0) {
	 var input = panel.data.br[0];
	 var pos = input.lastIndexOf ('/');
	 outputname = input.substring (pos+1) + '.annotated.' + modified + '.gff';
      } else {
	 outputname = 'annotated.' + modified + '.gff';
      }
   } else {
      // if it is still empty, the server will invent a name
      outputname = $.trim ($('#output-name').val());
   }

   var data = {};
   data.curator = $('#curator').val();
   data.source = $('#source').val();
   data.type = $('#type').val();
   data.modified = modified;
   var values = [];
   for (var i = 0; i < br_qids.length; i++) {
      var qid = br_qids[i];
      var kw = $('#br-kw-' + i + '-0').val();
      var ev = $('#br-ev-' + i + '-0').val();
      var st = $('#br-st-' + i + '-0').val();
      var gn = $('#br-gn-' + i + '-0').val();
      var de = $('#br-de-' + i + '-0').val();
      var os = $('#br-os-' + i + '-0').val();
      var le = $('#br-length-' + i).html();
      if (kw || ev || st || gn || de || os) {
	 values.push ({
	    qid: qid,
	    kw: kw,
	    ev: ev,
	    st: st,
	    gn: gn,
	    de: de,
	    os: os,
	    le: le
	 });
      }
   }
   data.data = values;
   var json = $.toJSON (data);

   // ...and send it...
   if (action == 'show') {
      // ...by AJAX
      $.ajax ({
	 context: panel,
	 url: 'ajax/' + panel.prefix + '-save',
	 data: {
	    data: json,
	    action: action,
	    outputname: outputname
	 },
	 success: function (data) {
	    $.fancybox (data, {
	       title: 'Annotations in GFF3 format',
	       width: 800,
	       height: 300,
	       autoDimensions: false,
	       transitionIn: 'none',
	       transitionOut: 'none'
	    });
	 },
	 error: function (xhr, textStatus, errorThrown) {
	    var msg;
	    if (xhr.responseText) {
	       msg = xhr.responseText;
	    } else {
	       msg = 'ERROR: ' + textStatus + ' - ' + ( errorThrown ? errorThrown : xhr.status );
	    }
	       $('#save-error').html (msg);
	    var dname = '#dialog-message-save-error';
	       $(dname).dialog ({
		  modal: true,
		  resizable: false,
		  buttons: {
		     Ok: function() {
			   $(this).dialog('close');
		     }
		  }
	       });
	 }
      });

   } else {
      // ...by a traditional form
      $('#data').val (json);
      $('#action').val (action);
      $('#outputname').val (outputname);
      $('#br-save-form').submit();
      // TBD - but how? How do I know that the download/save was successful?
      panel.saved = true;
      setSavedFlags();
   }
}
// called after each (successful?) save
function setSavedFlags() {
   $("[id|=br-edited]").each (function(i) {
      if (! $(this).is (':hidden')) {
	 var saved = $(this).attr ('id').replace ('edited', 'saved');
	 $('#' + saved).show();
	 $(this).hide();
      }
   });
}


// if 'checkbox' is checked, both other elements are disabled,
// otherwise they will be enabled
function toggleDisabled (checkbox, formElementId, labelElementId) {
    if (checkbox.checked) {
      $(jid (formElementId)).attr('disabled','disabled');
      $(jid (labelElementId)).removeClass ('disabled').addClass ('disabled');
    } else {
      $(jid (formElementId)).removeAttr('disabled');
      $(jid (labelElementId)).removeClass ('disabled');
    }
}

// make sure that the form 'element' has numeric or empty value 
function correctNumber (element) {
   var value = element.value;
   if (value != '') {
      var parsedValue = parseFloat (value);
      if (isNaN (parsedValue)) {
	 element.value = '';
	 $(element).trigger ('set');
      } else {
	 element.value = parsedValue;
      }
   }
}

// make sure that the form 'element' is empty or has an allowed value for a strand
var MAGENA_STRAND_ALLOWED = ['0', '1', '-1', '+1', '-', '+', '?'];
function correctStrand (element) {
   var value = element.value;
   if ($.inArray (value, MAGENA_STRAND_ALLOWED) == -1) {
      element.value = '';
      $(element).trigger ('set');
   }
}

//
function createInputFileElement (value) {
   return ('<input type="text" class="celltext input-file other-input-file" disabled="disabled" value="' + value + '" />');
}

//
function setFileTree() {
   $('#file-tree').fileTree ({
      root: '/',
      script: 'ajax/filetree'
   }, function (file) {
      // called when a file is selected   
      var value = (file.slice(-1) == '/' ? file.slice(0,-1) : file);
      if ($('#cb-multi').attr ('checked')) {
	 $('#input-files').append (createInputFileElement (value));
      } else {
	 $('.other-input-file').remove();
	 $('.first-input-file').val (value);
	 $('.first-input-file').trigger ('change');
      }
   });
}

//
function checkSplitForm (form) {
   $('.br-split-error').hide();
   var errors = false;

   if (! $('#br-split-input').val()) {
      $('#br-split-input-error').show();
      errors = true;
   }

   if (! $("input[@name='dir']:checked").val()) {
      $('#br-split-dir-error').show();
      errors = true;
   }

   return !errors;
}

//
// watching changes in input fields
//
// show "edited" if field's current and last values are different
function watchInput (event) {
   var watched = event.data.source;
   var lastValue = watched.data ('lastValue');
   var currentValue = watched.val();
   if (lastValue != currentValue) {
      var qindex = event.data.qindex;
      watched.data ('lastValue', currentValue);
      if (currentValue == '' && othersEmptyToo (qindex - 1)) {
         $('#br-edited-' + qindex).hide();
	 findPanel (event.data.panel).saved = true;
      } else {
	 $('#br-edited-' + qindex).show();
	 findPanel (event.data.panel).saved = false;
      }
   }
}
// return true if all input fields in the qindex's panel are empty
function othersEmptyToo (qindex) {
   var foundFilled = false;
   $(".e_" + qindex).each (function(i) {
      if ($(this).val() != '') {
	 foundFilled = true;
	 return false;
      }
   });
   return (! foundFilled);
}

//
// handling cookies
//
function handleCookie (element) {
   var cookieName = '_MAGENA_' + element.attr ('id');
   element.bind ('change', function(e) {
      var options = { path: '/', expires: 10 };
      $.cookie (cookieName, $(this).val(), options);
   });
   var cookie = $.cookie (cookieName);
   if (cookie) {
      element.val (cookie);
   } else {
      element.trigger ('change');
   }
}

//
// DB small sub-panels
//
// each DB panel has more targets to be filled by ajax: t1, t2...
function DBPanel (prefix, suffix, targets) {
   this.main = [];
   for (var i = 0; i < targets.length; i++) {
      this.main.push ($(jid (prefix + '-' + targets[i] + '-' + suffix)));
   }
   this.triangle = $(jid (prefix + '-db-triangle-' + suffix));
   this.loading = $(jid (prefix + '-db-loading-' + suffix));
   this.data = {};
   this.url = 'getterms';
   this.empty = true;

   // do something with data received from Ajax call
   this.process = function (data) {
      for (var i = 0; i < this.data.term.length; i++) {
	 this.main[i].html (data [ this.data.term[i] ]);
      }
      $('.minilink').fancybox ({
         hideOnContentClick: false,
         title: 'Interpro',
         speedIn: 10,
         speedOut:10,
         width:800,
         height:500,
         type:'iframe'
      });
   };

   return this;
}
function toggleDBPanel (panel) {
   if (panel.empty) {
      showPanel (panel);
      createPanelByAjax (panel);
   } else {
      panel.main[0].is (':hidden') ? showPanel (panel) : hidePanel (panel);
   }
}

