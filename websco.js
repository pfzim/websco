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

function f_restart_job(rb_guid, job_id)
{
	gi('job').style.display = 'none';
	f_show_form('/websco/get_runbook/' + rb_guid + '/' + job_id);
}

function f_get_job(guid)
{
	gi('loading').style.display = 'block';
	f_http(
		'/websco/get_job/' + guid,
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
				el = gi('job_restart');
				el.setAttribute('onclick', 'f_restart_job(\'' + data.runbook_guid + '\', ' + data.id + ');');
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


					html += '<tr><td>Instance ID: ' + data.instances[i].guid +'</td><td class="' + cl + '">' + data.instances[i].status +'</td></tr>';

					html += '<tr><td colspan="2"><b>Input parameters</b></td></tr>';
					for(j = 0; j < data.instances[i].params_in.length; j++)
					{
						html += '<tr><td>' + data.instances[i].params_in[j].name +'</td><td>' + data.instances[i].params_in[j].value +'</td></tr>';
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

						html += '<tr><td>' + data.instances[i].activities[j].sequence + '. ' + data.instances[i].activities[j].name +'</td><td class="' + cl + '">' + data.instances[i].activities[j].status +'</td></tr>';
					}
					html += '<tr><td colspan="2"><b>Output parameters</b></td></tr>';
					for(j = 0; j < data.instances[i].params_out.length; j++)
					{
						html += '<tr><td>' + data.instances[i].params_out[j].name +'</td><td><pre>' + data.instances[i].params_out[j].value +'</pre></td></tr>';
					}
				}

				el.innerHTML = html;
			}
		},
		guid
	);

	return false;
}

// make fake empty form
function f_new_permission(pid)
{
	var data = {
		code: 0,
		messsage: '',
		title: 'Add permissions',
		action: 'save_permission',
		fields: [
			{
				type: 'hidden',
				name: 'id',
				value: 0
			},
			{
				type: 'hidden',
				name: 'pid',
				value: pid
			},
			{
				type: 'string',
				name: 'dn',
				title: 'DN*',
				value: ''
			},
			{
				type: 'flags',
				name: 'allow_bits',
				title: 'Allow rights',
				value: 0,
				list: ['Execute']
			},
			{
				type: 'flags',
				name: 'apply_to_childs',
				title: 'Apply to childs',
				value: 0,
				list: ['Apply to childs', 'Replace childs']
			}
		]
	};

	on_received_form(data,'uform');
}

/*
form_data = {
	code = '0 - success, otherwise error',
	message = 'error message',
	title = 'form name',
	fields = [
		{
			type = 'hidden, list, date, number, string',
			name = 'post name',
			title = 'human readable caption'
			value = 'default value'
			list = [
				'select value 1',
				'list values',
				...
			]
		},
		...
	]
}
*/

function on_received_form(data, form_id)
{
	gi('loading').style.display = 'none';
	if(data.code)
	{
		f_notify(data.message, 'error');
	}
	else
	{
		gi(form_id + '-title').innerText = data.title;

		var el = gi(form_id + '-description');
		if(data.description && (data.description.length > 0))
		{
			el.innerText = data.description;
			el.style.display = 'block';
		}
		else
		{
			el.innerText = '';
			el.style.display = 'none';
		}

		el = gi(form_id + '-fields');
		el.innerHTML = '';
		html = '';
		for(i = 0; i < data.fields.length; i++)
		{
			if(data.fields[i].type == 'hidden')
			{
				html = '<input name="' + escapeHtml(data.fields[i].name) + '" type="hidden" value="' + escapeHtml(data.fields[i].value) + '" />';

				var wrapper = document.createElement('div');
				wrapper.innerHTML = html;
				el.appendChild(wrapper);
			}
			else if(data.fields[i].type == 'list' && data.fields[i].list)
			{
				html = '<div class="form-title"><label for="' + escapeHtml(form_id + data.fields[i].name) + '">'+ escapeHtml(data.fields[i].title) + ':</label></div>'
					+ '<select class="form-field" id="' + escapeHtml(form_id + data.fields[i].name) + '" name="'+ escapeHtml(data.fields[i].name) + '">'
					+ '<option value=""></option>';
				for(j = 0; j < data.fields[i].list.length; j++)
				{
					selected = ''
					if(data.fields[i].list[j] == data.fields[i].value)
					{
						selected = ' selected="selected"'
					}
					html += '<option value="' + escapeHtml(data.fields[i].list[j]) + '"' + selected + '>' + escapeHtml(data.fields[i].list[j]) + '</option>';
				}
				html += '</select>'
					+ '<div id="' + escapeHtml(form_id + data.fields[i].name) + '-error" class="form-error"></div>';

				var wrapper = document.createElement('div');
				wrapper.innerHTML = html;
				el.appendChild(wrapper);
			}
			else if(data.fields[i].type == 'flags' && data.fields[i].list)
			{
				value = parseInt(data.fields[i].value, 10);

				html = '<div class="form-title">' + escapeHtml(data.fields[i].title) + ':</div>';
				for(j = 0; j < data.fields[i].list.length; j++)
				{
					checked = '';
					if(value & (0x01 << j))
					{
						checked = ' checked="checked"';
					}

					html += '<span><input id="' + escapeHtml(form_id + data.fields[i].name) + '[' + j +']" name="' + escapeHtml(data.fields[i].name) + '[' + j +']" type="checkbox" value="1"' + checked + '/><label for="'+ escapeHtml(form_id + data.fields[i].name) + '[' + j + ']">' + escapeHtml(data.fields[i].list[j]) + '</label></span>'
				}
				html += '<div id="' + escapeHtml(form_id + data.fields[i].name) + '[0]-error" class="form-error"></div>';

				var wrapper = document.createElement('div');
				wrapper.innerHTML = html;
				el.appendChild(wrapper);
			}
			else if(data.fields[i].type == 'date')
			{
				var wrapper = document.createElement('div');
				wrapper.innerHTML = '<div class="form-title"><label for="' + escapeHtml(form_id + data.fields[i].name) + '">' + escapeHtml(data.fields[i].title) + ':</label></div>'
					+ '<input class="form-field" id="'+ escapeHtml(form_id + data.fields[i].name) + '" name="'+ escapeHtml(data.fields[i].name) + '" type="edit" value="' + escapeHtml(data.fields[i].value) + '"/>'
					+ '<div id="'+ escapeHtml(form_id + data.fields[i].name) + '-error" class="form-error"></div>';
				el.appendChild(wrapper);

				var picker = new Pikaday({
					field: document.getElementById(form_id + data.fields[i].name),
					format: 'DD.MM.YYYY'
				});
			}
			else
			{
				html = '<div class="form-title"><label for="'+ escapeHtml(form_id + data.fields[i].name) + '">' + escapeHtml(data.fields[i].title) + ':</label></div>'
					+ '<input class="form-field" id="' + escapeHtml(form_id + data.fields[i].name) + ']" name="'+ escapeHtml(data.fields[i].name) + '" type="edit" value="'+ escapeHtml(data.fields[i].value) + '"/>'
					+ '<div id="'+ escapeHtml(form_id + data.fields[i].name) + '-error" class="form-error"></div>';

				var wrapper = document.createElement('div');
				wrapper.innerHTML = html;
				el.appendChild(wrapper);
			}
		}

		html = '<br /><div class="f-right">'
			+ '<button class="button-accept" type="submit" onclick="return f_send_form(\'' + data.action + '\');">OK</button>'
			+ '&nbsp;'
			+ '<button class="button-decline" type="button" onclick="this.parentNode.parentNode.parentNode.parentNode.parentNode.style.display=\'none\'">Cancel</button>'
			+ '</div>';

		var wrapper = document.createElement('div');
		wrapper.innerHTML = html;
		el.appendChild(wrapper);

		gi(form_id +'-container').style.display='block';
	}
}

function f_show_form(url)
{
	var form_id = 'uform';
	gi('loading').style.display = 'block';
	f_http(
		url,
		on_received_form,
		form_id
	);

	return false;
}

function f_send_form(action)
{
	var form_id = 'uform';
	var form_data = {};
	var el = gi(form_id + '-fields');
	for(i = 0; i < el.elements.length; i++)
	{
		if(el.elements[i].name)
		{
			var err = gi(form_id + el.elements[i].name + '-error');
			if(err)
			{
				err.style.display = 'none';
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
	f_http(
		'/websco/' + action,
		function(data, form_id)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
			if(!data.code)
			{
				gi(form_id+'-container').style.display='none';
				//window.location = '?action=doc&id='+data.id;
				//window.location = window.location;
				//f_update_doc(data.data);
				//f_get_perms();
				on_saved(action, data);
			}
			else if(data.errors)
			{
				for(i = 0; i < data.errors.length; i++)
				{
					var el = gi(form_id + data.errors[i].name + '-error');
					if(el)
					{
						el.textContent = data.errors[i].msg;
						el.style.display = 'block';
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

function on_saved(action, data)
{
	if(action == 'start_runbook')
	{
		f_get_job(data.guid);
	}
	else if(action == 'save_permission')
	{
		f_get_perms(data.pid);
	}
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

function f_msg(text)
{
	gi('message-text').innerText = text;
	gi('message-box').style.display = 'block';
	return false;
}

function f_delete(ev, action)
{
	gi('loading').style.display = 'block';
	var el_src = ev.target || ev.srcElement;
	var id = el_src.parentNode.parentNode.getAttribute('data-id');
	f_http(
		'/websco/' + action + '/' + id,
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
	return f_async_ex(a.href);
}

function f_async_ex(url)
{
	gi('loading').style.display = 'block';
	f_http(
		url,
		function(data, el)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
			f_msg(data.message);
		},
		null
	);

	return false;
}

function f_show_hide(url)
{
	gi('loading').style.display = 'block';
	f_http(
		url,
		function(data, el)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
			if(!data.code)
			{
				f_get_perms(data.id);
			}
		},
		null
	);

	return false;
}

function f_get_perms(id)
{
	gi('loading').style.display = 'block';
	//a.parentNode.classList.add('active');

	f_http(
		'/websco/get_permissions/' + id,
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

				el = gi('add_new_permission');
				el.setAttribute('onclick', 'f_new_permission(' + data.id + ');');

				el = gi('show_hide');
				if(id == 0)
				{
					el.style.display = 'none';
				}
				else
				{
					if(data.flags & 0x0002)
					{
						el.innerText = 'Show folder in list';
						el.setAttribute('onclick', 'f_show_hide(\'/websco/show_folder/' + data.id + '\');');
					}
					else
					{
						el.innerText = 'Hide folder from list';
						el.setAttribute('onclick', 'f_show_hide(\'/websco/hide_folder/' + data.id + '\');');
					}
					el.style.display = 'inline';
				}

				el = gi(params);
				el.innerHTML = '';
				html = '';
				for(i = 0; i < data.permissions.length; i++)
				{
					html = '<td>' + data.permissions[i].id + '</td><td>' + data.permissions[i].group + '</td><td>' + data.permissions[i].perms + '</td>'
						+ '<td><span class="command" onclick="return f_show_form(\'/websco/get_permission/' + data.permissions[i].id + '\');">Edit</span> <span class="command" onclick="f_delete_perm(event);">Delete</span></td>';

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
			xhr.open('get', '/websco/expand/' + pid, true);
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
							text += '<li><span onclick="return f_expand(this, \'' + result.list[i].guid + '\');">+</span><a href="/websco/get_permissions/' + result.list[i].id + '" onclick="return f_get_perms(' + result.list[i].id + ');">'+escapeHtml(result.list[i].name)+'</a></li>';
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
