/**
	Copyright (c) 2008- Samuli Järvelä

	All rights reserved. This program and the accompanying materials
	are made available under the terms of the Eclipse Public License v1.0
	which accompanies this distribution, and is available at
	http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	this entire header must remain intact.
*/

jQuery.fn.exists = function() { return ($(this).length > 0); }

var preRequestCallback = null;
var postRequestCallback = null;
var protocolVersion = "1_5_0";

function getSessionInfo(success, fail) {
	request("GET", 'session/info/'+protocolVersion, success, fail);
}

function authenticate(username, pw, success, fail) {
	var data = JSON.stringify({username:username, password:generate_md5(pw), protocol_version:protocolVersion});
	request("POST", 'session/authenticate', success, fail, data);
}

function getFolders(success, fail) {
	request("GET", 'configuration/folders', success, fail);
}

function addFolder(name, path, createNonExisting, success, fail) {
	var data = JSON.stringify({name:name, path:path, create:createNonExisting ? "true" : "false"});
	request("POST", 'configuration/folders', success, fail, data);
}

function editFolder(id, name, path, success, fail) {
	var data = JSON.stringify({name:name, path:path});
	request("PUT", 'configuration/folders/'+id, success, fail, data);
}

function removeFolder(id, success, fail) {
	request("DELETE", 'configuration/folders/'+id, success, fail);
}

function getUserFolders(user, success, fail) {
	request("GET", 'configuration/users/'+user+'/folders', success, fail);
}

function getFolderUsers(folder, success, fail) {
	request("GET", 'configuration/folders/'+folder+'/users', success, fail);
}

function addFolderUsers(id, users, success, fail) {
	var data = JSON.stringify(users);
	request("POST", 'configuration/folders/'+id+'/users', success, fail, data);
}

function removeFolderUsers(id, users, success, fail) {
	var data = JSON.stringify(users);
	request("POST", 'configuration/folders/'+id+'/remove_users', success, fail, data);
}

function addUserFolder(user, id, name, success, fail) {
	var data = JSON.stringify({id:id, name:name});
	request("POST", 'configuration/users/'+user+'/folders', success, fail, data);
}

function editUserFolder(user, id, name, success, fail) {
	var data = JSON.stringify({name:name});
	request("PUT", 'configuration/users/'+user+'/folders/'+id, success, fail, data);
}

function removeUserFolder(user, id, success, fail) {
	request("DELETE", 'configuration/users/'+user+'/folders/'+id, success, fail);
}

function getUsers(success, fail) {
	request("GET", 'configuration/users', success, fail);
}

function addUser(name, pw, email, permission, success, fail) {
	var data = JSON.stringify({name:name, password:generate_md5(pw), email:email, "permission_mode":permission});
	request("POST", 'configuration/users', success, fail, data);
}

function changePassword(id, pw, success, fail) {
	var data = JSON.stringify({"new":generate_md5(pw)});
	request("PUT", 'configuration/users/'+id+'/password', success, fail, data);
}

function editUser(id, name, email, permission, success, fail) {
	var data = JSON.stringify({name:name, email:email, "permission_mode":permission});
	request("PUT", 'configuration/users/'+id, success, fail, data);
}

function removeUser(id, success, fail) {
	request("DELETE", 'configuration/users/'+id, success, fail);
}

function getUsersGroups(user, success, fail) {
	request("GET", 'configuration/users/'+user+'/groups', success, fail);
}

function addUsersGroups(user, groups, success, fail) {
	var data = JSON.stringify(groups);
	request("POST", 'configuration/users/'+user+'/groups', success, fail, data);
}

function getUserGroups(success, fail) {
	request("GET", 'configuration/usergroups', success, fail);
}

function getGroupUsers(id, success, fail) {
	request("GET", 'configuration/usergroups/'+id+'/users', success, fail);
}

function addUserGroup(name, desc, success, fail) {
	var data = JSON.stringify({name:name, description:desc});
	request("POST", 'configuration/usergroups', success, fail, data);
}

function editUserGroup(id, name, desc, success, fail) {
	var data = JSON.stringify({name:name, description:desc});
	request("PUT", 'configuration/usergroups/'+id, success, fail, data);
}

function addGroupUsers(id, users, success, fail) {
	var data = JSON.stringify(users);
	request("POST", 'configuration/usergroups/'+id+'/users', success, fail, data);
}

function removeGroupUsers(id, users, success, fail) {
	var data = JSON.stringify(users);
	request("POST", 'configuration/usergroups/'+id+'/remove_users', success, fail, data);
}

function removeUserGroup(id, success, fail) {
	request("DELETE", 'configuration/usergroups/'+id, success, fail);
}

function request(type, url, success, fail, data) {
	if (preRequestCallback) preRequestCallback();
	
	var t = type;
	if (getSession() != null && getSession().features["limited_http_methods"]) {
		if (t == 'PUT' || t == 'DELETE') t = 'POST';
	}

	$.ajax({
		type: t,
		url: "../r.php/"+url,
		data: data,
		dataType: "json",
		success: function(result) {
			if (postRequestCallback) postRequestCallback();
			success(result.result);
		},
		error: function (xhr, desc, exc) {
			if (postRequestCallback) postRequestCallback();
			
			var e = xhr.responseText;
			if (!e) fail({code:999, error:"Unknown error", details:"Request failed, no response received"});
			else if (e.substr(0, 1) != "{") fail({code:999, error:"Unknown error", details:"Invalid response received: " + e});
			else fail(JSON.parse(e));
		},
		beforeSend: function (xhr) {
			xhr.setRequestHeader("mollify-http-method", type);
		}
	});
}