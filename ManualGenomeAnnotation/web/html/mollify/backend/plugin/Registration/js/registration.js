/**
	Copyright (c) 2008- Samuli Järvelä

	All rights reserved. This program and the accompanying materials
	are made available under the terms of the Eclipse Public License v1.0
	which accompanies this distribution, and is available at
	http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	this entire header must remain intact.
*/

var servicePath = null;
var session = null;
var preRequestCallback = null;
var postRequestCallback = null;
var protocolVersion = "1_5_0";

function onError(error) {
	var errorHtml = $.template("<div class='error'><div class='title'>${title}</div><div class='details'>${details}</div></div>");
	$("body").html(errorHtml, {title: error.error, details: error.details });	
}

function getSession() {
	return session;
}

function getSessionInfo(success, fail) {
	request("GET", 'session/info/'+protocolVersion, success, fail);
}

function register(name, pw, email, success, fail) {
	var data = JSON.stringify({name:name, password:generate_md5(pw), email:email});
	request("POST", 'registration/create', success, fail, data);
}

function confirm(email, key, success, fail) {
	var data = JSON.stringify({email:email, key:key});
	request("POST", 'registration/confirm', success, fail, data);
}

function request(type, url, cb, fail, data) {
	if (preRequestCallback) preRequestCallback();
	
	var t = type;
	if (getSession() != null && getSession().features["limited_http_methods"]) {
		if (t == 'PUT' || t == 'DELETE') t = 'POST';
	}

	$.ajax({
		type: t,
		url: servicePath+"r.php/"+url,
		data: data,
		dataType: "json",
		success: function(result) {
			if (postRequestCallback) postRequestCallback();
			cb(result.result);
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