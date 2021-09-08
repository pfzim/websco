var g_pid = 0;

function gi(name)
{
	return document.getElementById(name);
}

function escapeHtml(text)
{
  return (text+'')
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}

function json2url(data)
{
	return Object.keys(data).map(
		function(k)
		{
			return encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
		}
	).join('&');
}

function formatbytes(bytes, decimals) {
   if(bytes == 0) return '0 B';
   var k = 1024;
   var dm = decimals || 2;
   var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
   var i = Math.floor(Math.log(bytes) / Math.log(k));
   return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function f_xhr()
{
	try { return new XMLHttpRequest(); } catch(e) {}
	try { return new ActiveXObject("Msxml3.XMLHTTP"); } catch(e) {}
	try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); } catch(e) {}
	try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); } catch(e) {}
	try { return new ActiveXObject("Msxml2.XMLHTTP"); } catch(e) {}
	try { return new ActiveXObject("Microsoft.XMLHTTP"); } catch(e) {}
	console.log("ERROR: XMLHttpRequest undefined");
	return null;
}

function f_http(url, _f_callback, _callback_params, content_type, data)
{
	var f_callback = null;
	var callback_params = null;

	if(typeof _f_callback !== 'undefined') f_callback = _f_callback;
	if(typeof _callback_params !== 'undefined') callback_params = _callback_params;
	if(typeof content_type === 'undefined') content_type = null;
	if(typeof data === 'undefined') data = null;

	var xhr = f_xhr();
	if(!xhr)
	{
		if(f_callback)
		{
			f_callback({code: 1, message: "AJAX error: XMLHttpRequest unsupported"}, callback_params);
		}

		return false;
	}

	xhr.open((content_type || data)?"post":"get", url, true);
	xhr.onreadystatechange = function()
	{
		if(xhr.readyState == 4)
		{
			var result;
			if(xhr.status == 200)
			{
				try
				{
					result = JSON.parse(xhr.responseText);
				}
				catch(e)
				{
					result = {code: 1, message: "Response: "+xhr.responseText};
				}
			}
			else
			{
				result = {code: 1, message: "AJAX error code: "+xhr.status};
			}

			if(f_callback)
			{
				f_callback(result, callback_params);
			}
		}
	};

	if(content_type)
	{
		xhr.setRequestHeader('Content-Type', content_type);
	}

	xhr.send(data);

	return true;
}


function f_start_runbook(form_id)
{
	var form_data = {};
	var el = gi(form_id);
	for(i = 0; i < el.elements.length; i++)
	{
		if(el.elements[i].name)
		{
			var err = gi(el.elements[i].name + '-error');
			if(err)
			{
				err.style.display='none';
			}

			if(el.elements[i].type == 'checkbox')
			{
				if(el.elements[i].checked)
				{
					form_data[el.elements[i].name] = el.elements[i].value;
				}
			}
			else if(el.elements[i].type == 'select-one')
			{
				if(el.elements[i].selectedIndex != -1)
				{
					form_data[el.elements[i].name] = el.elements[i].value;
				}
			}
			else
			{
				form_data[el.elements[i].name] = el.elements[i].value;
			}
		}
	}

	//alert(json2url(form_data));
	//return;

	gi('loading').style.display = 'block';
	f_http("websco.php?" + json2url(form_data),
		function(data, params)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
			if(!data.code)
			{
				gi(params + '-container').style.display='none';
				//gi('message-text').innerText = 'Ранбук успешно запущен!';
				//gi('message').style.display='block';
				//window.location = '?action=doc&id='+data.id;
				//window.location = window.location;
				//f_update_doc(data.data);
				f_get_job(data.guid);
			}
			else if(data.errors)
			{
				for(i = 0; i < data.errors.length; i++)
				{
					var el = gi(data.errors[i].name + "-error");
					if(el)
					{
						el.textContent = data.errors[i].msg;
						el.style.display='block';
					}
				}
			}
		},
		form_id
	);

	return false;
}

function f_get_job(guid)
{
	gi('loading').style.display = 'block';
	f_http(
		'websco.php?' + json2url({'action': 'get_job', 'guid': guid}),
		function(data, guid)
		{
			gi('loading').style.display = 'none';
			if(data.code)
			{
				f_notify(data.message, 'error');
			}
			else
			{
				var el = gi('runbook_title');
				el.innerText = data.name;
				el = gi('job_guid');
				el.innerText = data.guid;
				el = gi('job_status');
				el.innerText = data.status;
				if(data.status == 'Completed')
				{
					el.className = 'status-ok';
				}
				else
				{
					el.className = 'status-warn';
				}

				el = gi('job_user');
				el.innerText = data.user;
				el = gi('job_update');
				el.setAttribute('onclick', 'f_get_job(\'' + guid + '\');');
				gi('job').style.display = 'block';
				el = gi('job_table_data');
				el.innerHTML = '';
				html = '';
				for(i = 0; i < data.instances.length; i++)
				{
					if(data.instances[i].status == 'success')
					{
						cl = 'status-ok';
					}
					else
					{
						cl = 'status-warn';
					}

					
					html += '<tr><td>Instance ID:' + data.instances[i].guid +'</td><td class="' + cl + '">' + data.instances[i].status +'</td></tr>';

					html += '<tr><td colspan="2"><b>Input parameters</b></td></tr>';
					for(j = 0; j < data.instances[i].params_in.length; j++)
					{
						html += '<tr><td>' + data.instances[i].params_in[j].name +'</td><td>' + data.instances[i].params_in[j].value +'</td></tr>';
					}
					html += '<tr><td colspan="2"><b>Output parameters</b></td></tr>';
					for(j = 0; j < data.instances[i].params_out.length; j++)
					{
						html += '<tr><td>' + data.instances[i].params_out[j].name +'</td><td><pre>' + data.instances[i].params_out[j].value +'</pre></td></tr>';
					}
					html += '<tr><td colspan="2"><b>Activities</b></td></tr>';
					for(j = 0; j < data.instances[i].activities.length; j++)
					{
						if(data.instances[i].activities[j].status == 'success')
						{
							cl = 'status-ok';
						}
						else if(data.instances[i].activities[j].status == 'warning')
						{
							cl = 'status-warn';
						}
						else
						{
							cl = 'status-err';
						}

						html += '<tr><td>- ' + data.instances[i].activities[j].name +'</td><td class="' + cl + '">' + data.instances[i].activities[j].status +'</td></tr>';
					}
				}

				el.innerHTML = html;
			}
		},
		guid
	);

	return false;
}

function f_show_runbook(a, form_id)
{
	gi('loading').style.display = 'block';
	f_http(
		a.href,
		function(data, params)
		{
			gi('loading').style.display = 'none';
			if(data.code)
			{
				f_notify(data.message, 'error');
			}
			else
			{
				var el = gi(params);
				el.innerHTML = '';
				html = '';
				for(i = 0; i < data.fields.length; i++)
				{
					if(data.fields[i].type == 'header')
					{
						html = '<h3>' + escapeHtml(data.fields[i].title) + '</h3>';

						var wrapper = document.createElement('div');
						wrapper.innerHTML = html;
						el.appendChild(wrapper);
					}
					else if(data.fields[i].type == 'hidden')
					{
						html = '<input name="' + escapeHtml(data.fields[i].name) + '" type="hidden" value="' + escapeHtml(data.fields[i].value) + '" />';

						var wrapper = document.createElement('div');
						wrapper.innerHTML = html;
						el.appendChild(wrapper);
					}
					else if(data.fields[i].type == 'list' && data.fields[i].list)
					{
						html = '<div class="form-title"><label for="param['+ escapeHtml(data.fields[i].guid) + ']">'+ escapeHtml(data.fields[i].name) + ':</label></div>'
								+ '<select class="form-field" name="param['+ escapeHtml(data.fields[i].guid) + ']">'
								+ '<option value=""></option>';
						for(j = 0; j < data.fields[i].list.length; j++)
						{
							html += '<option value="' + escapeHtml(data.fields[i].list[j]) + '">' + escapeHtml(data.fields[i].list[j]) + '</option>';
						}
						html += '</select>';

						var wrapper = document.createElement('div');
						wrapper.innerHTML = html;
						el.appendChild(wrapper);
					}
					else if(data.fields[i].type == 'date')
					{
						var wrapper = document.createElement('div');
						wrapper.innerHTML = '<div class="form-title"><label for="param['+ escapeHtml(data.fields[i].guid) + ']">' + escapeHtml(data.fields[i].name) + ':</label></div>'
								+ '<input class="form-field" id="param['+ escapeHtml(data.fields[i].guid) + ']" name="param['+ escapeHtml(data.fields[i].guid) + ']" type="edit" value=""/>'
								+ '<div id="param['+ escapeHtml(data.fields[i].guid) + ']-error" class="form-error"></div>';
						el.appendChild(wrapper);

						var picker = new Pikaday({
							field: document.getElementById('param['+ escapeHtml(data.fields[i].guid) + ']'),
							format: 'DD.MM.YYYY'
						});
					}
					else
					{
						html = '<div class="form-title"><label for="param['+ escapeHtml(data.fields[i].guid) + ']">' + escapeHtml(data.fields[i].name) + ':</label></div>'
						+ '<input class="form-field" id="param['+ escapeHtml(data.fields[i].guid) + ']" name="param['+ escapeHtml(data.fields[i].guid) + ']" type="edit" value=""/>'
						+ '<div id="param['+ escapeHtml(data.fields[i].guid) + ']-error" class="form-error"></div>';

						var wrapper = document.createElement('div');
						wrapper.innerHTML = html;
						el.appendChild(wrapper);
					}

				}

				html = '<div class="f-right">'
					+ '<button class="button-accept" type="submit" onclick="return f_start_runbook(\'runbook\');">Запустить</button>'
					+ '&nbsp;'
					+ '<button class="button-decline" type="button" onclick="this.parentNode.parentNode.parentNode.parentNode.style.display=\'none\'">Отмена</button>'
					+ '</div>';

				var wrapper = document.createElement('div');
				wrapper.innerHTML = html;
				el.appendChild(wrapper.firstChild);

				gi(params+'-container').style.display='block';
			}
		},
		form_id
	);
	
	return false;
}

function f_notify(text, type)
{
	var el;
	var temp;
	el = gi('notify-block');
	if(!el)
	{
		temp = document.getElementsByTagName('body')[0];
		el = document.createElement('div');
		el.id = 'notify-block';
		el.style.top = '0px';
		el.style.right = '0px';
		el.className = 'notifyjs-corner';
		temp.appendChild(el);
	}

	temp = document.createElement('div');
	temp.innerHTML = '<div class="notifyjs-wrapper notifyjs-hidable"><div class="notifyjs-arrow"></div><div class="notifyjs-container" style=""><div class="notifyjs-bootstrap-base notifyjs-bootstrap-'+escapeHtml(type)+'"><span data-notify-text="">'+escapeHtml(text)+'</span></div>';
	temp = el.appendChild(temp.firstChild);

	setTimeout(
		(function(el)
		{
			return function() {
				el.parentNode.removeChild(el);
			};
		})(temp),
		5000
	);
}

function f_delete(ev, action)
{
	gi('loading').style.display = 'block';
	var el_src = ev.target || ev.srcElement;
	var id = el_src.parentNode.parentNode.getAttribute('data-id');
	f_http(
		"websco.php?"+json2url({'action': action, 'id': id }),
		function(data, el)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
			if(!data.code)
			{
				var row = el.parentNode.parentNode;
				row.parentNode.removeChild(row);
			}
		},
		el_src
	);
}

function f_delete_perm(ev)
{
	f_delete(ev, 'delete_permission');
}

function f_async(a)
{
	gi('loading').style.display = 'block';
	f_http(
		a.href,
		function(data, el)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
		},
		null
	);
	
	return false;
}

function f_save(form_id)
{
	var form_data = {};
	var el = gi(form_id);
	for(i = 0; i < el.elements.length; i++)
	{
		if(el.elements[i].name)
		{
			var err = gi(el.elements[i].name + '-error');
			if(err)
			{
				err.style.display='none';
			}

			if(el.elements[i].type == 'checkbox')
			{
				if(el.elements[i].checked)
				{
					form_data[el.elements[i].name] = el.elements[i].value;
				}
			}
			else if(el.elements[i].type == 'select-one')
			{
				if(el.elements[i].selectedIndex != -1)
				{
					form_data[el.elements[i].name] = el.elements[i].value;
				}
			}
			else
			{
				form_data[el.elements[i].name] = el.elements[i].value;
			}
		}
	}

	//alert(json2url(form_data));
	//return;

	gi('loading').style.display = 'block';
	f_http("websco.php?action=save_" + form_id,
		function(data, params)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
			if(!data.code)
			{
				gi(params+'-container').style.display='none';
				//window.location = '?action=doc&id='+data.id;
				//window.location = window.location;
				//f_update_doc(data.data);
				f_get_perms();
			}
			else if(data.errors)
			{
				for(i = 0; i < data.errors.length; i++)
				{
					var el = gi(data.errors[i].name + "-error");
					if(el)
					{
						el.textContent = data.errors[i].msg;
						el.style.display='block';
					}
				}
			}
		},
		form_id,
		'application/x-www-form-urlencoded',
		json2url(form_data)
	);

	return false;
}

function f_get_perms(id)
{
	gi('loading').style.display = 'block';
	//a.parentNode.classList.add('active');

	f_http(
		"websco.php?" + json2url({'action': 'get_permissions', 'id': id }),
		function(data, params)
		{
			gi('loading').style.display = 'none';
			if(data.code)
			{
				f_notify(data.message, 'error');
			}
			else
			{
				var el = gi('section_name');
				el.innerText = data.name;
				g_pid = data.id;
				
				el = gi(params);
				el.innerHTML = '';
				html = '';
				for(i = 0; i < data.permissions.length; i++)
				{
					html = '<td>' + data.permissions[i].id + '</td><td>' + data.permissions[i].group + '</td><td>' + data.permissions[i].perms + '</td>'
						+ '<td><span class="command" onclick="f_edit(' + data.permissions[i].id + ', \'permission\');">Edit</span> <span class="command" onclick="f_delete_perm(event);">Delete</span></td>';

					var tr = document.createElement('tr');
					tr.setAttribute("data-id", data.permissions[i].id);
					tr.innerHTML = html;
					el.appendChild(tr);
				}

				//gi(params).style.display='block';
			}
		},
		'table-data'
	);
	
	return false;
}

function f_edit(ev, form_id)
{
	var id = 0;
	if(ev)
	{
		//var el_src = ev.target || ev.srcElement;
		//id = el_src.parentNode.parentNode.getAttribute('data-id');
		id = ev;
	}
	if(!id)
	{
		var form_data = {};
		var el = gi(form_id);
		for(i = 0; i < el.elements.length; i++)
		{
			if(el.elements[i].name)
			{
				var err = gi(el.elements[i].name + '-error');
				if(err)
				{
					err.style.display='none';
				}

				if(el.elements[i].name == 'id')
				{
					el.elements[i].value = id;
				}
				else if(el.elements[i].name == 'pid')
				{
					el.elements[i].value = g_pid;
				}
				else
				{
					if(el.elements[i].type == 'checkbox')
					{
						el.elements[i].checked = false;
					}
					else
					{
						el.elements[i].value = '';
					}
				}
			}
		}
		gi(form_id + '-container').style.display='block';
	}
	else
	{
		gi('loading').style.display = 'block';
		f_http(
			"websco.php?"+json2url({'action': 'get_' + form_id, 'id': id }),
			function(data, params)
			{
				gi('loading').style.display = 'none';
				if(data.code)
				{
					f_notify(data.message, "error");
				}
				else
				{
					var el = gi(params);
					for(i = 0; i < el.elements.length; i++)
					{
						if(el.elements[i].name)
						{
							if(data.data[el.elements[i].name])
							{
								if(el.elements[i].type == 'checkbox')
								{
									el.elements[i].checked = (parseInt(data.data[el.elements[i].name], 10) != 0);
								}
								else
								{
									el.elements[i].value = data.data[el.elements[i].name];
								}
							}
						}
					}
					gi(params+'-container').style.display='block';
				}
			},
			form_id
		);
	}
}

function f_expand(self, _pid)
{
	var pid = _pid;
	var el = gi('expand'+pid);
	if(el)
	{
		el.parentNode.removeChild(el);
		self.innerText = '+';
	}
	else
	{
		var xhr = f_xhr();
		if(xhr)
		{
			xhr.open("get", "websco.php?action=expand&guid="+pid, true);
			xhr.onreadystatechange = function()
			{
				if(xhr.readyState == 4)
				{
					var result;
					if(xhr.status == 200)
					{
						try
						{
							result = JSON.parse(xhr.responseText);
						}
						catch(e)
						{
							result = {code: 1, status: "Response: "+xhr.responseText};
						}
					}
					else
					{
						result = {code: 1, status: "AJAX error code: "+xhr.status};
					}
					if(result.code)
					{
						f_notify(result.status, 'error');
					}
					else
					{
						var text = '<ul>';
						for(var i = 0; i < result.list.length; i++)
						{
							text += '<li><span onclick="return f_expand(this, \'' + result.list[i].guid + '\');">+</span><a href="?action=get_permissions&id=' + result.list[i].id + '" onclick="return f_get_perms(' + result.list[i].id + ');">'+escapeHtml(result.list[i].name)+'</a></li>';
						}
						text += '</ul>';
						var div = document.createElement('div');
						div.id = 'expand' + pid;
						//div.className = 'expand-list';
						div.innerHTML = text;
						//gi("row"+id).cells[0].appendChild(div);
						self.parentNode.appendChild(div);
						//self.parentNode.insertBefore(div, self.nextSibling);
						self.innerText = '-';
					}
				}
			};
			xhr.send(null);
		}
	}
	return false;
}
