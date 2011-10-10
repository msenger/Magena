/**
	Copyright (c) 2008- Samuli Järvelä

	All rights reserved. This program and the accompanying materials
	are made available under the terms of the Eclipse Public License v1.0
	which accompanies this distribution, and is available at
	http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	this entire header must remain intact.
*/

function MollifyUserGroupsConfigurationView() {
	var that = this;
	
	this.pageUrl = "users/groups.html";
	this.users = null;
	this.groups = null;
	this.groupUsers = null;
		
	this.onLoadView = onLoadView;
	
	function onLoadView() {
		loadScript("users/common.js", that.init);
	}
	
	this.init = function() {		
		$("#button-add-group").click(that.openAddGroup);
		$("#button-remove-group").click(that.onRemoveGroup);
		$("#button-edit-group").click(that.onEditGroup);
		$("#button-refresh-groups").click(that.refresh);

		$("#groups-list").jqGrid({        
			datatype: "local",
			multiselect: false,
			autowidth: true,
			height: '100%',
		   	colNames:['ID', 'Name', 'Description'],
		   	colModel:[
			   	{name:'id',index:'id', width:60, sortable:true, sorttype:"int"},
		   		{name:'name',index:'name', width:200, sortable:true},
		   		{name:'description',index:'description', width:300, sortable:true},
		   	],
		   	sortname:'id',
		   	sortorder:'asc',
			onSelectRow: function(id){
				that.onGroupSelectionChanged();
			}
		});

		$("#button-add-group-users").click(that.openAddGroupUsers);
		$("#button-remove-group-users").click(that.onRemoveGroupUsers);
		$("#button-refresh-group-users").click(that.refreshGroupUsers);

		$("#group-users-list").jqGrid({        
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
				that.onGroupUserSelectionChanged();
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
	
	this.getUserGroup = function(id) {
		return that.groups[id];
	}

	this.getGroupUser = function(id) {
		return that.groupUsers[id];
	}

	this.getSelectedGroup = function() {
		return $("#groups-list").getGridParam("selrow");
	}

	this.getSelectedGroupUsers = function() {
		return $("#group-users-list").getGridParam("selarrrow");
	}

	this.refresh = function() {
		getUserGroups(that.refreshGroups, onServerError);
	}
	
	this.refreshGroups = function(groups) {
		that.groups = {};

		var grid = $("#groups-list");
		grid.jqGrid('clearGridData');
		
		for(var i=0;i < groups.length;i++) {
			var group = groups[i];
			that.groups[group.id] = group;
			grid.jqGrid('addRowData', group.id, group);
		}
		
		that.onGroupSelectionChanged();
		
		getUsers(that.refreshUsers, onServerError);
	}
	
	this.refreshUsers = function(users) {
		that.users = {};
		
		for (var i=0; i < users.length; i++) {
			user = users[i];
			that.users[user.id] = user;
		}
	}
	
	this.refreshGroupUsers = function() {
		var id = $("#groups-list").getGridParam("selrow");
		if (!id) return;
		
		getGroupUsers(id, that.onRefreshGroupUsers, onServerError);
	}
	
	this.onRefreshGroupUsers = function(groupUsers) {
		that.groupUsers = {};
		
		var grid = $("#group-users-list");
		grid.jqGrid('clearGridData');

		for (var i=0; i < groupUsers.length; i++) {
			var groupUser = groupUsers[i];
			that.groupUsers[groupUser.id] = groupUser;
			grid.jqGrid('addRowData', groupUser.id, groupUser);
		}
				
		that.onGroupUserSelectionChanged();
	}
		
	this.onGroupSelectionChanged = function() {
		var group = that.getSelectedGroup();
		var selected = (group != null);
		if (selected) group = that.getUserGroup(group);
		
		that.groupUsers = null;
		
		enableButton("button-remove-group", selected);
		enableButton("button-edit-group", selected);
		
		if (that.groups.length == 0) {
			$("#group-details-info").html('<div class="message">Click "Add Group" to create a new user group</div>');
		} else {
			if (selected) {
				$("#group-users-list").jqGrid('setGridWidth', $("#group-details").width(), true);
				$("#group-details-info").html("<h1>Group '"+group.name+"'</h1>");
				
				that.refreshGroupUsers();
			} else {
				$("#group-details-info").html('<div class="message">Select a group from the list to view details</div>');
			}
		}
		
		if (!selected) {
			$("#group-details-data").hide();
		} else {
			$("#group-details-data").show();
		}
	}

	this.onGroupUserSelectionChanged = function() {
		var selected = (that.getSelectedGroupUsers().length > 0);
		enableButton("button-remove-group-users", selected);		
	}
	
	this.validateGroupData = function() {
		$("#group-dialog > .form-data").removeClass("invalid");
	
		var result = true;
		if ($("#group-name-field").val().length == 0) {
			$("#group-name").addClass("invalid");
			result = false;
		}
		return result;
	}

	this.openAddGroup = function() {
		that.openAddEditGroup(null);
	}
	
	this.openAddEditGroup = function(id) {
		if (!that.addEditGroupDialogInit) {
			that.addEditGroupDialogInit = true;
					
			$("#group-dialog").dialog({
				autoOpen: false,
				bgiframe: true,
				height: 'auto',
				width: 270,
				modal: true,
				resizable: false,
				buttons: {}
			});
		}
		
		var buttons = {
			Cancel: function() {
				$(this).dialog('close');
			}
		}

		var action = function() {
			if (!that.validateGroupData()) return;
			
			var name = $("#group-name-field").val();
			var desc = $("#group-description-field").val();
			
			onSuccess = function() {
				$("#group-dialog").dialog('close');
				that.refresh();
			}

			if (id)
				editUserGroup(id, name, desc, onSuccess, onServerError);
			else
				addUserGroup(name, desc, onSuccess, onServerError);
		}
		
		if (id)
			buttons["Edit"] = action;
		else
			buttons["Add"] = action;
		
		$("#group-dialog").dialog('option', 'buttons', buttons);
		
		if (id) {
			var group = that.getUserGroup(id);
			$("#group-name-field").val(group.name);
			$("#group-description-field").val(group.description);
			$("#group-dialog").dialog('option', 'title', 'Edit Group');
		} else {
			$("#group-name-field").val("");
			$("#group-description-field").val("");
			$("#group-dialog").dialog('option', 'title', 'Add Group');
		}
		
		$("#group-dialog").dialog('open');
	}
	
	this.onRemoveGroup = function() {
		var id = that.getSelectedGroup();
		if (id == null) return;
		removeUserGroup(id, that.refresh, onServerError);
	}
	
	this.onEditGroup = function() {
		var id = that.getSelectedGroup();
		if (id == null) return;
		that.openAddEditGroup(id);
	}

	this.openAddGroupUsers = function() {
		if (that.users == null) return;
		
		var availableUsers = that.getAvailableGroupUsers();
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
						$("#add-group-users-dialog").dialog('close');
						that.refreshGroupUsers();
					}
					
					addGroupUsers(that.getSelectedGroup(), sel, onSuccess, onServerError);
				}
			}
					
			$("#add-group-users-dialog").dialog({
				bgiframe: true,
				height: 'auto',
				width: 330,
				modal: true,
				resizable: true,
				autoOpen: false,
				title: "Add Users to Group",
				buttons: buttons
			});
		}
		
		$("#add-group-users-dialog").dialog('open');
	}
	
	this.getAvailableGroupUsers = function() {
		var result = [];
		for (id in that.users) {
			if (!that.groupUsers[id])
				result.push(that.users[id]);
		}
		return result;
	}

	this.onRemoveGroupUsers = function() {
		var sel = that.getSelectedGroupUsers();
		if (sel.length == 0) return;
		removeGroupUsers(that.getSelectedGroup(), sel, that.refreshGroupUsers, onServerError);
	}
}