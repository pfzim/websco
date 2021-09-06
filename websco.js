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
				gi('message-text').innerText = 'Ранбук успешно запущен!';
				gi('message').style.display='block';
				//window.location = '?action=doc&id='+data.id;
				//window.location = window.location;
				//f_update_doc(data.data);
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

function f_show_runbook(id, form_id)
{
	gi('loading').style.display = 'block';
	f_http("websco.php?" + json2url({'action': 'get_runbook', 'guid': id }),
		function(data, params)
		{
			gi('loading').style.display = 'none';
			if(data.code)
			{
				f_notify(data.message, "error");
			}
			else
			{
				var el = gi(params + '-container');
				el.innerHTML = data.html;
				gi(params+'-container').style.display='block';
			}
		},
		form_id
	);
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
