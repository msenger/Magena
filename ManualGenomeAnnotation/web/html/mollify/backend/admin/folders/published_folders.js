/**
 * Copyright (c) 2008- Samuli Järvelä
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
 * this entire header must remain intact.
 */

function MollifyPublishedFoldersConfigurationView() {
	var that = this;
	
	this.pageUrl = "folders/published_folders.html";
	this.folders = null;
		
	this.onLoadView = function() {
		$("#button-add-folder").click(that.openAddFolder);
		$("#button-remove-folder").click(that.onRemoveFolder);
		$("#button-edit-folder").click(that.openEditFolder);
		$("#button-refresh-folders").click(that.refresh);

		$("#folders-list").jqGrid({        
			datatype: "local",
			multiselect: false,
			autowidth: true,
			height: '100%',
		   	colNames:['ID', 'Name','Path'],
		   	colModel:[
			   	{name:'id',index:'id', width:20, sortable:true, sorttype:"int"},
		   		{name:'name',index:'name', width:200, sortable:true},
				{name:'path',index:'path',width:150, sortable:true},
		   	],
		   	sortname:'id',
		   	sortorder:'asc',
			onSelectRow: function(id){
				that.onFolderSelectionChanged();
			}
		});
		
		$("#button-add-folder-users").click(that.openAddFolderUsers);
		$("#button-remove-folder-users").click(that.onRemoveFolderUsers);
		$("#button-refresh-folder-users").click(that.refreshFolderUsers);

		$("#folder-users-list").jqGrid({        
			datatype: "local",
			multiselect: true,
			autowidth: true,
			height: '100%',
		   	colNames:['ID', 'Name'],
		   	colModel:[
			   	{name:'id',index:'id', width:60, sortable:true, sorttype:"int"},
		   		{name:'name',index:'name', width:200, sortable:true}
		   	],
		   	sortname:'id',
		   	sortorder:'asc',
			onSelectRow: function(id){
				that.onFolderUserSelectionChanged();
			}
		});
		
		$("#add-users-list").jqGrid({        
			datatype: "local",
			autowidth: true,
			multiselect: true,
		   	colNames:['ID', 'Name'],
		   	colModel:[
			   	{name:'id',index:'id', width:60, sortable:true, sorttype:"int"},
		   		{name:'name',index:'name', width:200, sortable:true}
		   	],
		   	sortname:'id',
		   	sortorder:'asc'
		});

		that.refresh();
	}
	
	this.getFolder = function(id) {
		return that.folders[id];
	}
	
	this.getSelectedFolder = function() {
		return $("#folders-list").getGridParam("selrow");
	}
	
	this.getSelectedFolderUsers = function() {
		return $("#folder-users-list").getGridParam("selarrrow");
	}
	
	this.refresh = function() {
		getFolders(that.onRefreshFolders, onServerError);
	}
	
	this.onRefreshFolders = function(folders) {
		that.folders = {};

		var grid = $("#folders-list");
		grid.jqGrid('clearGridData');
		
		for(var i=0;i < folders.length;i++) {
			var folder = folders[i];
			that.folders[folder.id] = folder;

			grid.jqGrid('addRowData', folder.id, folder);
		}
		
		that.onFolderSelectionChanged();
		
		getUsers(that.refreshUsers, onServerError);
	}
	
	this.refreshUsers = function(users) {
		that.users = {};
		
		for (var i=0; i < users.length; i++) {
			user = users[i];
			that.users[user.id] = user;
		}
	}
	
	this.refreshFolderUsers = function() {
		var id = that.getSelectedFolder();
		if (!id) return;
		
		getFolderUsers(id, that.onRefreshFolderUsers, onServerError);
	}
	
	this.onRefreshFolderUsers = function(folderUsers) {
		that.folderUsers = {};
		
		var grid = $("#folder-users-list");
		grid.jqGrid('clearGridData');

		for (var i=0; i < folderUsers.length; i++) {
			var folderUser = folderUsers[i];
			that.folderUsers[folderUser.id] = folderUser;
			grid.jqGrid('addRowData', folderUser.id, folderUser);
		}
				
		that.onFolderUserSelectionChanged();
	}
		
	this.onFolderSelectionChanged = function() {
		var folder = that.getSelectedFolder();
		var selected = (folder != null);
		if (selected) folder = that.getFolder(folder);
		
		enableButton("button-remove-folder", selected);
		enableButton("button-edit-folder", selected);
		
		that.folderUsers = null;
		
		if (that.folders.length == 0) {
			$("#folder-details-info").html('<div class="message">Click "Add Folder" to publish a folder</div>');
		} else {
			if (selected) {
				$("#folder-users-list").jqGrid('setGridWidth', $("#folder-details").width(), true);
				$("#folder-details-info").html("<h1>Folder '"+folder.name+"'</h1>");
				
				that.refreshFolderUsers();
			} else {
				$("#folder-details-info").html('<div class="message">Select a folder from the list to view details</div>');
			}
		}
		
		if (!selected) {
			$("#folder-details-data").hide();
		} else {
			$("#folder-details-data").show();
		}	
	}
	
	this.onFolderUserSelectionChanged = function() {
		var selected = (that.getSelectedFolderUsers().length > 0);
		enableButton("button-remove-folder-users", selected);
	}
	
	this.openAddFolder = function() {
		that.addEditFolder(null);
	}

	this.openEditFolder = function() {
		that.addEditFolder(that.getSelectedFolder());
	}
	
	this.addEditFolder = function(id) {
		if (!that.addEditFolderDialogInit) {
			that.addEditFolderDialogInit = true;
			
			$("#script-location").html(getScriptLocation());
					
			$("#folder-dialog").dialog({
				autoOpen: false,
				bgiframe: true,
				height: 'auto',
				width: 500,
				modal: true,
				resizable: false,
				buttons: {},
				title: ''
			});
		}
		
		var buttons = {
			Cancel: function() {
				$(this).dialog('close');
			}
		}
		
		var action = function() {
			$("#folder-dialog > .form-data").removeClass("invalid");
			$("#folder-path-validation-info").html("");
		
			var result = true;
			if ($("#folder-name-field").val().length == 0) {
				$("#folder-name").addClass("invalid");
				result = false;
			}
			if ($("#folder-path-field").val().length == 0) {
				$("#folder-path").addClass("invalid");
				result = false;
			}
			if (!result) return;
			
			var name = $("#folder-name-field").val();
			var path = $("#folder-path-field").val();
			
			onSuccess = function() {
				$("#folder-dialog").dialog('close');
				that.refresh();
			}
			
			onFail = function(err) {
				if (err.code == 105) {
					$("#folder-path").addClass("invalid");
					$("#folder-path-validation-info").html("Folder does not exist");
					return;
				}
				onServerError(err);
			}

			if (id)
				editFolder(id, name, path, onSuccess, onFail);
			else {
				var createNonExisting = $("#create-nonexisting").attr('checked');
				addFolder(name, path, createNonExisting, onSuccess, onFail);
			}
		}

		if (id)
			buttons["Edit"] = action;
		else
			buttons["Add"] = action;

		$("#folder-dialog").dialog('option', 'buttons', buttons);
		$("#folder-dialog > .form-data").removeClass("invalid");
		$("#folder-path-validation-info").html("");

		if (id) {
			var folder = that.getFolder(id);
			$("#folder-name-field").val(folder.name);
			$("#folder-path-field").val(folder.path);
			$("#folder-dialog").dialog('option', 'title', 'Edit Folder');
			$("#create-nonexisting-panel").hide();
		} else {
			$("#folder-name-field").val("");
			$("#folder-path-field").val("");
			$("#folder-dialog").dialog('option', 'title', 'Add Folder');
			$("#create-nonexisting").attr('checked', false);
			$("#create-nonexisting-panel").show();
		}
		
		$("#folder-dialog").dialog('open');
	}

	this.onRemoveFolder = function() {
		var id = that.getSelectedFolder();
		if (id == null) return;
		removeFolder(id, that.refresh, onServerError);
	}
	
	this.openAddFolderUsers = function() {
		if (that.users == null) return;
		
		var availableUsers = that.getAvailableFolderUsers();
		if (availableUsers.length == 0) {
			alert("No more users available");
			return;
		}
		
		var grid = $("#add-users-list");
		grid.jqGrid('clearGridData');
		
		for(var i=0;i < availableUsers.length;i++) {
			grid.jqGrid('addRowData', availableUsers[i].id, availableUsers[i]);
		}

		if (!that.addUserDialogInit) {
			that.addUserDialogInit = true;
			
			var buttons = {
				Cancel: function() {
					$(this).dialog('close');
				},
				Add: function() {
					var sel = $("#add-users-list").getGridParam("selarrrow");
					if (sel.length == 0) return;
	
					var onSuccess = function() {
						$("#add-folder-users-dialog").dialog('close');
						that.refreshFolderUsers();
					}
					
					addFolderUsers(that.getSelectedFolder(), sel, onSuccess, onServerError);
				}
			}
					
			$("#add-folder-users-dialog").dialog({
				bgiframe: true,
				height: 'auto',
				width: 330,
				modal: true,
				resizable: true,
				autoOpen: false,
				title: "Add Folder to Users",
				buttons: buttons
			});
		}
		
		$("#add-folder-users-dialog").dialog('open');
	}
	
	this.getAvailableFolderUsers = function() {
		var result = [];
		for (id in that.users) {
			if (!that.folderUsers[id])
				result.push(that.users[id]);
		}
		return result;
	}

	this.onRemoveFolderUsers = function() {
		var sel = that.getSelectedFolderUsers();
		if (sel.length == 0) return;
		removeFolderUsers(that.getSelectedFolder(), sel, that.refreshFolderUsers, onServerError);
	}
}