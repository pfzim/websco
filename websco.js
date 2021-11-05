var g_pid = 0;
var autocomplete_current_focus = -1;

function gi(name)
{
	return document.getElementById(name);
}

function escapeHtml(text)
{
  return (''+text)
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
	f_show_form(g_link_prefix + 'runbook_get/' + rb_guid + '/' + job_id);
}

function f_get_job(guid)
{
	gi('loading').style.display = 'block';
	f_http(
		g_link_prefix + 'job_get/' + guid,
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
				el = gi('job_run_date');
				el.innerText = data.run_date;
				el = gi('job_modified_date');
				el.innerText = data.modified_date;
				el = gi('job_sid');
				el.innerText = data.sid + ' (' + data.sid_name + ')';
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
				el.setAttribute('onclick', 'f_get_job(\'' + escapeHtml(guid) + '\');');
				el = gi('job_restart');
				el.setAttribute('onclick', 'f_restart_job(\'' + escapeHtml(data.runbook_guid) + '\', ' + data.id + ');');
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

					html += '<tr><td>' + LL.InstanceID + ': ' + escapeHtml(data.instances[i].guid) +'</td><td class="' + cl + '">' + escapeHtml(data.instances[i].status) +'</td></tr>';

					html += '<tr><td colspan="2"><b>' + LL.InputParameters + '</b></td></tr>';
					for(j = 0; j < data.instances[i].params_in.length; j++)
					{
						html += '<tr><td>' + escapeHtml(data.instances[i].params_in[j].name) +'</td><td>' + escapeHtml(data.instances[i].params_in[j].value) +'</td></tr>';
					}
					html += '<tr><td colspan="2"><b>' + LL.Activities + '</b></td></tr>';
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

						html += '<tr><td><a href="' + g_link_prefix + 'job_activity_get/' + data.instances[i].activities[j].id + '" onclick="return f_get_activity(this.href);">' + escapeHtml(data.instances[i].activities[j].sequence) + '. ' + escapeHtml(data.instances[i].activities[j].name) +'</a></td><td class="' + cl + '">' + escapeHtml(data.instances[i].activities[j].status) +'</td></tr>';
					}
					html += '<tr><td colspan="2"><b>' + LL.OutputParameters + '</b></td></tr>';
					for(j = 0; j < data.instances[i].params_out.length; j++)
					{
						html += '<tr><td>' + escapeHtml(data.instances[i].params_out[j].name) +'</td><td><pre>' + escapeHtml(data.instances[i].params_out[j].value) +'</pre></td></tr>';
					}
				}

				el.innerHTML = html;
			}
		},
		guid
	);

	return false;
}

function f_get_activity(url)
{
	gi('loading').style.display = 'block';
	f_http(
		url,
		function(data, guid)
		{
			gi('loading').style.display = 'none';
			if(data.code)
			{
				f_notify(data.message, 'error');
			}
			else
			{
				//var el = gi('activity_title');
				//el.innerText = data.guid;

				gi('activity').style.display = 'block';
				var el = gi('activity_table_data');
				el.innerHTML = '';
				html = '';
				for(j = 0; j < data.params.length; j++)
				{
					html += '<tr><td>' + escapeHtml(data.params[j].name) +'</td><td><pre>' + escapeHtml(data.params[j].value) +'</pre></td></tr>';
				}

				el.innerHTML = html;
			}
		},
		'guid'
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
				list: ['List', 'Execute']
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

function f_append_fields(el, fields, form_id, spoiler_id)
{
	//console.log('f_append_fields' + spoiler_id);
	for(var i = 0, ec = fields.length; i < ec; i++)
	{
		if(fields[i].type == 'hidden')
		{
			html = '<input name="' + escapeHtml(fields[i].name) + '" type="hidden" value="' + escapeHtml(fields[i].value) + '" />';

			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			el.appendChild(wrapper);
		}
		else if(fields[i].type == 'list' && fields[i].list)
		{
			html = '<div class="form-title"><label for="' + escapeHtml(form_id + fields[i].name) + '">'+ escapeHtml(fields[i].title) + ':</label></div>'
				+ '<select class="form-field" id="' + escapeHtml(form_id + fields[i].name) + '" name="'+ escapeHtml(fields[i].name) + '">'
				+ '<option value=""></option>';
			for(j = 0; j < fields[i].list.length; j++)
			{
				selected = ''
				if(fields[i].values)
				{
					if(fields[i].values[j] == fields[i].value)
					{
						selected = ' selected="selected"'
					}
				}
				else if(fields[i].list[j] == fields[i].value)
				{
					selected = ' selected="selected"'
				}
				html += '<option value="' + escapeHtml(fields[i].values ? fields[i].values[j] : fields[i].list[j]) + '"' + selected + '>' + escapeHtml(fields[i].list[j]) + '</option>';
			}
			html += '</select>'
				+ '<div id="' + escapeHtml(form_id + fields[i].name) + '-error" class="form-error"></div>';

			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			el.appendChild(wrapper);
		}
		else if(fields[i].type == 'flags' && fields[i].list)
		{
			value = parseInt(fields[i].value, 10);

			html = '<div class="form-title">' + escapeHtml(fields[i].title) + ':</div>';
			for(j = 0; j < fields[i].list.length; j++)
			{
				checked = '';
				if(value & (0x01 << j))
				{
					checked = ' checked="checked"';
				}

				html += '<span><input id="' + escapeHtml(form_id + fields[i].name) + '[' + j +']" name="' + escapeHtml(fields[i].name) + '[' + j +']" type="checkbox" value="' + escapeHtml(fields[i].values?fields[i].values[j]:'1') + '"' + checked + '/><label for="'+ escapeHtml(form_id + fields[i].name) + '[' + j + ']">' + escapeHtml(fields[i].list[j]) + '</label></span>'
			}
			html += '<div id="' + escapeHtml(form_id + fields[i].name) + '[0]-error" class="form-error"></div>';

			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			el.appendChild(wrapper);
		}
		else if(fields[i].type == 'datetime')
		{
			var wrapper = document.createElement('div');
			wrapper.innerHTML = '<div class="form-title"><label for="' + escapeHtml(form_id + fields[i].name) + '">' + escapeHtml(fields[i].title) + ':</label></div>'
				+ '<input class="form-field" id="'+ escapeHtml(form_id + fields[i].name) + '" name="'+ escapeHtml(fields[i].name) + '" type="edit" value="' + escapeHtml(fields[i].value) + '"/>'
				+ '<div id="'+ escapeHtml(form_id + fields[i].name) + '-error" class="form-error"></div>';
			el.appendChild(wrapper);

			flatpickr(
				gi(form_id + fields[i].name),
				{
					allowInput: true,
					enableTime: true,
					time_24hr: true,
					defaultHour: 0,
					defaultMinute: 0,
					dateFormat: "d.m.Y H:i"
				}
			);
		}
		else if(fields[i].type == 'time')
		{
			var wrapper = document.createElement('div');
			wrapper.innerHTML = '<div class="form-title"><label for="' + escapeHtml(form_id + fields[i].name) + '">' + escapeHtml(fields[i].title) + ':</label></div>'
				+ '<input class="form-field" id="'+ escapeHtml(form_id + fields[i].name) + '" name="'+ escapeHtml(fields[i].name) + '" type="edit" value="' + escapeHtml(fields[i].value) + '"/>'
				+ '<div id="'+ escapeHtml(form_id + fields[i].name) + '-error" class="form-error"></div>';
			el.appendChild(wrapper);

			flatpickr(
				gi(form_id + fields[i].name),
				{
					allowInput: true,
					enableTime: true,
					noCalendar: true,
					time_24hr: true,
					defaultHour: 0,
					defaultMinute: 0,
					dateFormat: "H:i"
				}
			);
		}
		else if(fields[i].type == 'date')
		{
			var wrapper = document.createElement('div');
			wrapper.innerHTML = '<div class="form-title"><label for="' + escapeHtml(form_id + fields[i].name) + '">' + escapeHtml(fields[i].title) + ':</label></div>'
				+ '<input class="form-field" id="'+ escapeHtml(form_id + fields[i].name) + '" name="'+ escapeHtml(fields[i].name) + '" type="edit" value="' + escapeHtml(fields[i].value) + '"/>'
				+ '<div id="'+ escapeHtml(form_id + fields[i].name) + '-error" class="form-error"></div>';
			el.appendChild(wrapper);

			flatpickr(
				gi(form_id + fields[i].name),
				{
					allowInput: true,
					dateFormat: "d.m.Y"
				}
			);
			/*
			var picker = new Pikaday({
				field: gi(form_id + fields[i].name),
				format: 'DD.MM.YYYY'
			});
			*/
		}
		else if(fields[i].type == 'password')
		{
			html = '<div class="form-title"><label for="'+ escapeHtml(form_id + fields[i].name) + '">' + escapeHtml(fields[i].title) + ':</label></div>'
				+ '<input class="form-field" id="' + escapeHtml(form_id + fields[i].name) + '" name="'+ escapeHtml(fields[i].name) + '" type="password" value=""/>'
				+ '<div id="'+ escapeHtml(form_id + fields[i].name) + '-error" class="form-error"></div>';

			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			el.appendChild(wrapper);
		}
		else if(fields[i].type == 'upload')
		{
			html = '<div class="form-title"><label for="'+ escapeHtml(form_id + fields[i].name) + '">' + escapeHtml(fields[i].title) + ':</label></div>'
				+ '<span class="form-upload" id="' + escapeHtml(form_id + fields[i].name) + '-file">&nbsp;</span> <a href="#" onclick="gi(\'' + escapeHtml(form_id + fields[i].name) + '\').click(); return false;"/>' + LL.SelectFile + '</a>'
				+ '<input id="' + escapeHtml(form_id + fields[i].name) + '" name="'+ escapeHtml(fields[i].name) + '" type="file" accept="' + escapeHtml(fields[i].accept?fields[i].accept:'') + '" style="display: none"/>'
				+ '<div id="' + escapeHtml(form_id + fields[i].name) + '-error" class="form-error"></div>';

			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			el.appendChild(wrapper);

			gi(form_id + fields[i].name).onchange = function(name) {
				return function() {
					gi(name + '-file').textContent = this.files.item(0).name;
				}
			}(form_id + fields[i].name);
		}
		else if(fields[i].type == 'spoiler')
		{
			spoiler_id++;

			var wrapper = document.createElement('div');
			wrapper.setAttribute('onclick', 'f_toggle_spoiler(\'' + escapeHtml(form_id + '_spoiler_' + spoiler_id) + '\');');
			wrapper.className = 'spoiler';
			wrapper.textContent = fields[i].title;
			el.appendChild(wrapper);

			wrapper = document.createElement('div');
			wrapper.id = form_id + '_spoiler_' + spoiler_id;
			wrapper.style.display = 'none';
			el.appendChild(wrapper);

			spoiler_id = f_append_fields(wrapper, fields[i].fields, form_id, spoiler_id);
		}
		else
		{
			var placeholder = '';
			if(fields[i].placeholder)
			{
				placeholder = '" placeholder="' + fields[i].placeholder;
			}

			html = '<div class="form-title"><label for="'+ escapeHtml(form_id + fields[i].name) + '">' + escapeHtml(fields[i].title) + ':</label></div>'
				+ '<input class="form-field" id="' + escapeHtml(form_id + fields[i].name) + '" name="'+ escapeHtml(fields[i].name) + '" type="edit" value="'+ escapeHtml(fields[i].value) + placeholder + '"/>'
				+ '<div id="'+ escapeHtml(form_id + fields[i].name) + '-error" class="form-error"></div>';

			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			el.appendChild(wrapper);

			if(fields[i].autocomplete)
			{
				autocomplete_create(gi(form_id + fields[i].name), fields[i].autocomplete);
			}
		}
	}

	return spoiler_id;
}

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

		f_append_fields(el, data.fields, form_id, 0);

		html = '<br /><div class="f-right">'
			+ '<button class="button-accept" type="submit" onclick="return f_send_form(\'' + data.action + '\');">' + LL.OK + '</button>'
			+ '&nbsp;'
			+ '<button class="button-decline" type="button" onclick="this.parentNode.parentNode.parentNode.parentNode.parentNode.style.display=\'none\'">' + LL.Cancel + '</button>'
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

			/*
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
			*/
		}
	}

	//alert(json2url(form_data));
	//return;

	gi('loading').style.display = 'block';
	f_http(
		g_link_prefix + action,
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
		null,                                    //'application/x-www-form-urlencoded',
		new FormData(gi(form_id + '-fields'))    //json2url(form_data)
	);

	return false;
}

function on_saved(action, data)
{
	if(action == 'runbook_start')
	{
		f_get_job(data.guid);
	}
	else if(action == 'permission_save')
	{
		//f_get_perms(data.pid);
		window.location = window.location;
	}
	else if(action == 'user_save')
	{
		window.location = window.location;
	}
	else if(action == 'register')
	{
		f_msg(data.message);
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

function on_action_success(el, action, data)
{
	if(action == 'user_deactivate')
	{
		window.location = window.location;
	}
	else if(action == 'user_activate')
	{
		window.location = window.location;
	}
	else
	{
		var row = el.parentNode.parentNode;
		row.parentNode.removeChild(row);
	}
}

function f_call_action(ev, action)
{
	gi('loading').style.display = 'block';
	var el_src = ev.target || ev.srcElement;
	var id = el_src.parentNode.parentNode.getAttribute('data-id');
	f_http(
		g_link_prefix + action,
		function(data, params)
		{
			gi('loading').style.display = 'none';
			f_notify(data.message, data.code?"error":"success");
			if(!data.code)
			{
				on_action_success(params.el, params.action, data);
			}
		},
		{el: el_src, action: action},
		'application/x-www-form-urlencoded',
		json2url({id: id})
	);
}

function f_delete_perm(ev)
{
	if(window.confirm(LL.ConfirmDelete))
	{
		f_call_action(ev, 'permission_delete');
	}

	return false;
}

function f_deactivate_user(ev)
{
	f_call_action(ev, 'user_deactivate');
}

function f_delete_user(ev)
{
	if(window.confirm(LL.ConfirmDelete))
	{
		f_call_action(ev, 'user_delete');
	}
}

function f_activate_user(ev)
{
	f_call_action(ev, 'user_activate');
}

function f_confirm_async(a)
{
	if(window.confirm(LL.ConfirmOperation))
	{
		return f_async_ex(a.href);
	}

	return false;
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

function f_show_hide(url, id)
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
		null,
		'application/x-www-form-urlencoded',
		json2url({id: id})
	);

	return false;
}

/*
function f_search(url, query)
{
	//var fd = new FormData()
	//fd.append('search', query);

    var form = document.createElement('form');
    var search = document.createElement('input');

    form.method = 'POST';
    form.action = url;

    search.type = 'hidden';
    search.name = 'search';
    search.value = query;
    form.appendChild(search);

    document.body.appendChild(form);

    form.submit();

	return false;
}
*/

function f_search(f)
{
    //f.action = f.action + '/' + encodeURIComponent(gi('search').value);
    //f.submit();

	window.location = f.action + '/' + encodeURIComponent(gi('search').value);

	return false;
}

function f_toggle_spoiler(id)
{
	var el = gi(id);
	if(el.style.display === 'none')
	{
		el.style.display = 'block';
	}
	else
	{
		el.style.display = 'none';
	}

	return false;
}

function f_get_perms(id)
{
	gi('loading').style.display = 'block';
	//a.parentNode.classList.add('active');

	f_http(
		g_link_prefix + 'permissions_get/' + id,
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
				el.setAttribute('onclick', 'f_show_form(\'new_permission/' + data.id + '\');');

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
						el.setAttribute('onclick', 'f_show_hide(\'' + g_link_prefix + 'folder_show\', ' + data.id + ');');
					}
					else
					{
						el.innerText = 'Hide folder from list';
						el.setAttribute('onclick', 'f_show_hide(\'' + g_link_prefix + 'folder_hide\', ' + data.id + ');');
					}
					el.style.display = 'inline';
				}

				el = gi(params);
				el.innerHTML = '';
				html = '';
				for(i = 0; i < data.permissions.length; i++)
				{
					html = '<td>' + data.permissions[i].id + '</td><td>' + data.permissions[i].group + '</td><td>' + data.permissions[i].perms + '</td>'
						+ '<td><span class="command" onclick="return f_show_form(\'' + g_link_prefix + 'permission_get/' + data.permissions[i].id + '\');">Edit</span> <span class="command" onclick="f_delete_perm(event);">Delete</span></td>';

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

function autocomplete_on_click(id, value)
{
	gi(id).value = value;
	autocomplete_destroy();
}

function autocomplete_on_input(ev)
{
	var el = ev.target || ev.srcElement;

	if(!el.value )
	{
		return false;
	}

	action = el.getAttribute('data-action');

	f_http(
		g_link_prefix + action,
		function(data, el)
		{
			gi('loading').style.display = 'none';
			if(data.code)
			{
				f_notify(data.message, 'error');
			}
			else
			{
				var a, b, i;
				autocomplete_current_focus = -1;

				autocomplete_destroy(null);
				a = document.createElement('DIV');
				a.setAttribute('id', 'autocomplete-container');
				a.setAttribute('class', 'autocomplete-items');

				el.parentNode.appendChild(a);
				for(i = 0; i < data.list.length; i++)
				{
					b = document.createElement('DIV');
					b.innerHTML = (''+data.list[i]).replace(new RegExp('(' + el.value + ')', 'i'), '<strong>$1</strong>');
					b.setAttribute('onclick', 'autocomplete_on_click(\'' + el.id + '\', \'' + data.list[i] + '\');');
					a.appendChild(b);
				}
			}
		},
		el,
		'application/x-www-form-urlencoded',
		json2url({search: el.value})
	);
}

function autocomplete_on_keydown(e)
{
	var el = e.target || e.srcElement;
	var x = gi('autocomplete-container');
	if(!x)
	{
		return;
	}

	items = x.getElementsByTagName('div');

	if(e.keyCode == 40)
	{
		autocomplete_current_focus++;
		utocomplete_add_active(items);
	}
	else if(e.keyCode == 38) //up
	{
		autocomplete_current_focus--;
		utocomplete_add_active(items);
	}
	else if (e.keyCode == 13)
	{
		e.preventDefault();
		if (autocomplete_current_focus > -1)
		{
			if(items)
			{
				items[autocomplete_current_focus].click();
			}
		}
	}
}

function autocomplete_create(input, action)
{
	input.setAttribute('data-action', action);
	//input.setAttribute('autocomplete', 'off');
	input.addEventListener('input', autocomplete_on_input);
	input.addEventListener('keydown', autocomplete_on_keydown);
}

function utocomplete_add_active(items)
{
	if(!items) return false;

	autocomplete_remove_active(items);

	if(autocomplete_current_focus >= items.length)
	{
		autocomplete_current_focus = 0;
	}

	if(autocomplete_current_focus < 0)
	{
		autocomplete_current_focus = (items.length - 1);
	}

	items[autocomplete_current_focus].classList.add('autocomplete-active');
}

function autocomplete_remove_active(items)
{
	for(var i = 0; i < items.length; i++)
	{
		items[i].classList.remove('autocomplete-active');
	}
}

function autocomplete_destroy(e)
{
	var el = gi('autocomplete-container');
	if(el)
	{
		el.parentNode.removeChild(el);
	}
}


// https://github.com/jfriend00/docReady
// https://github.com/dmilisic/docReady
(function() {
    "use strict";
    var readyFired = false;

    // call this when the document is ready
    // this function protects itself against being called more than once
    function docReady() {
        if (!readyFired) {
            // this must be set to true before we start calling callbacks
            readyFired = true;
            // TODO: Enter your code here

			/*execute a function when someone clicks in the document:*/
        	if(document.addEventListener)
        	{
				document.addEventListener('click', 	autocomplete_destroy);
			}
			else
			{
            	document.attachEvent('onclick', autocomplete_destroy);
			}
        }
    }

    function readyStateChange() {
        if ( document.readyState === "complete" ) {
            docReady();
        }
    }

    // if document already ready to go, schedule the docReady function to run
    // IE only safe when readyState is "complete", others safe when readyState is "interactive"
    if (document.readyState === "complete" || (!document.attachEvent && document.readyState === "interactive")) {
        setTimeout(docReady, 1);
    } else {
        // otherwise install event handlers
        if (document.addEventListener) {
            // first choice is DOMContentLoaded event
            document.addEventListener("DOMContentLoaded", docReady, false);
            // backup is window load event
            window.addEventListener("load", docReady, false);
        } else {
            // must be IE
            document.attachEvent("onreadystatechange", readyStateChange);
            window.attachEvent("onload", docReady);
        }
    }
})();


