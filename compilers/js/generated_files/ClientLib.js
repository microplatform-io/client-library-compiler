/*
	
The MIT License (MIT)

Copyright (c) {{@_COPYRIGHT_YEAR_@}} {{@_COPYRIGHT_NAME_@}}

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
	
*/
function {{@_PLATFORM_NAME_@}}ClientLib (params) {

	// Public
	this.api = new {{@_PLATFORM_NAME_@}}API();

	// Private
	this.reconnectAttempt = 0;
	this.reconnectDelay = 0;
	this.app_id  = params.app_id; this.api.app_id = this.app_id;
	this.product = params.product; this.api.product = this.product;
	this.token   = params.token; this.api.token = this.token;
	this.timerSocketURL;

	// Check if the user is supplying a protocol, host and port
	if (params.protocol) this.protocol = params.protocol;
	if (params.host) this.host = params.host;
	if (params.port) this.port = params.port;
	
	// Confirm the library was initialized properly
	if (!this.app_id || !this.token || !this.product) {
		console.log("Error" + "\n\n" + "{{@_PLATFORM_NAME_@}}ClientLib needs to be initialized with an 'app_id', 'product', and 'token'");
		alert ("Error" + "\n\n" + "{{@_PLATFORM_NAME_@}}ClientLib needs to be initialized with an 'app_id', 'product', and 'token'");
	} else {
		// Setup the socket connection
		initializeSocket(this);
		getSocketURLThenConnect(this);
	}



/* ------------ PUBLIC METHODS ------------ */


	/**
	 *  Cancel all pending requests
	 */
	this.cancelAllRequests = function() {
		this.connection.cancelAllRequests();
	}


/* ------------ PRIVATE METHODS ------------ */


	/**
	 *  If the library hasn't been initialized with a protocol, host and port we need to
	 *  fetch those missing values from microplatform.io so we can setup a socket connection
	 */
	function getSocketURLThenConnect(context) {
		// Cancel the timer that checks if a previous socket url fetch times out
		cancelSocketURLTimer(context);
		
		// Check if we have everything we need to connect
		if (context.protocol && context.host && context.port) {
			// We have everything
			connectSocket(context, context.protocol, context.host, context.port)
			return;
		}
	
		// Generate the JSONP callback function
		var callback = "callback_" + guid();
		window[callback] = function(response) {
			// Cancel the timer that checks if a previous socket url fetch times out
			cancelSocketURLTimer(context);
		
			// Get the socket URL components
			var protocol = context.protocol ? context.protocol : response.protocol;
			var host = context.host ? context.host : response.host;
			var port = context.port ? context.port : response.port;
			
			// Confirm we have all the necessary URL components
			if (!protocol) {
				var error = new Error("microplatform.io failed to return a 'protocol'");
			} else if (!host) {
				var error = new Error("microplatform.io failed to return a 'host'");
			} else if (!port) {
				var error = new Error("microplatform.io failed to return a 'port'");
			}
			if (error) {
				try {
					context.onConnectError(error);
				} catch (err) {
					
				}
				reconnectSocket(context);
				return;
			}
			
			// We have everything so connect
			connectSocket(context, protocol, host, port);
	    }
    
		// Start a timer that if fired indicates the attempt to fetch the socket url timed out
		context.timerSocketURL = setTimeout(function() { socketURLTimedOut(context); }, {{@_PLATFORM_NAME_@}}Constants.connectionTimeout * 1000);
		
		// Fetch the socket URL using JSONP
		var protocol = "http";
		if (window.location.protocol == "https:") {
			protocol = "https";
		}
		var url = protocol + "://" + context.product + ".microplatform.io/server?callback=" + callback;
		var fileref = document.createElement('script');
        fileref.setAttribute("type","text/javascript");
        fileref.setAttribute("src", url);	
        document.head.appendChild(fileref);
	} 
	 
	/**
	 *  Initialize the {{@_PLATFORM_NAME_@}}SocketLib
	 */
	function initializeSocket(context) {

		// Setup the socket connection
		context.connection = new {{@_PLATFORM_NAME_@}}SocketLib();

		// Register event handlers
		context.connection.onConnected = function () {
			try {
				context.onConnected();
			} catch (err) {
				
			}
			cancelReconnectAttempt(context);
		}
		context.connection.onDisconnected = function (error) {
			reconnectSocket(context);
		}
		context.connection.onFailedToConnect = function (error) {
			try {
				context.onConnectError(error);
			} catch (err) {
				
			}
			reconnectSocket(context);

		}
		context.connection.onRequestReceived = function (request) {
			//context.onRequestReceived(request);
		}
		context.connection.onShouldReconnectNow = function () {
			context.reconnectDelay = 0;
			reconnectSocket(context);
		}
		context.connection.onSocketActivity = function (idle) {
			try {
				context.onSocketActivity(idle);
			} catch (err) {
				
			}
		}

		// Pass the connection to the API class
		context.api.connection = context.connection;
	}
	
	/**
	 *
	 */
	function connectSocket(context, protocol, host, port) {
		var url = protocol + "://" + host + ":" + port;
		context.connection.connect(url);
	}

	/**
	 *
	 */
	function reconnectSocket(context) {
		clearTimeout(context.timerReconnect);
		context.reconnectAttempt++;
		context.reconnectDelay += {{@_PLATFORM_NAME_@}}Constants.reconnectionDelay;
		if (context.reconnectDelay > {{@_PLATFORM_NAME_@}}Constants.reconnectionDelayMax) {
			context.reconnectDelay = {{@_PLATFORM_NAME_@}}Constants.reconnectionDelayMax;
		}
		if (context.onPreparingToReconnect) {
			try {
				context.onPreparingToReconnect(context.reconnectAttempt, context.reconnectDelay);
			} catch (err) {
				
			}
		}
		context.timerReconnect = setTimeout(function() {
			if (context.onReconnecting) {
				try {
					context.onReconnecting(context.reconnectAttempt);
				} catch (err) {
					
				}
			}
			getSocketURLThenConnect(context);
		}, context.reconnectDelay * 1000);
	}

	/**
	 *
	 */
	function cancelReconnectAttempt(context) {
		context.reconnectAttempt = 0;
		context.reconnectDelay = 0;
		clearTimeout(context.timerReconnect);
	}
	
	/**
	 *
	 */
	function cancelSocketURLTimer(context) {
		if (context.timerSocketURL) {
			clearTimeout(context.timerSocketURL);
			context.timerSocketURL = null;
		}
	}
	
	/**
	 *
	 */
	function socketURLTimedOut(context) {
		cancelSocketURLTimer(context);
		var error = new Error("Attempt to get the socket url from microplatform.io timed out");
		try {
			context.onConnectError(error);
		} catch (err) {
			
		}
		reconnectSocket(context);
	}

	/**
	 *
	 */
	function guid(dashes) {
	    function _p8(s) {
	        var p = (Math.random().toString(16)+"000000000").substr(2,8);
	        return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
	    }
	    return _p8() + _p8(dashes) + _p8(dashes) + _p8();
	}
	
/* ------------ EVENTS ------------ */


	/**
	 *  Fired upon a successful connection
	 */
	this.onConnected = function () {
	}

	/**
	 *  Fired upon a connection error
	 */
	this.onConnectError = function (error) {
	}

	/**
	 *  Fired upon an attempt to reconnect
	 */
	this.onReconnecting = function (attempt) {
	}

	/**
	 *  Fired when a request is received from the server
	 */
	this.onRequestReceived = function (request) {
	}

	/**
	 *  Fired when the socket goes from an active to idle state or vice versa
	 */
	this.onSocketActivity = function (idle) {
	}

	/*
	 *
	 */
	this.onPreparingToReconnect = function (attempt, delay) {

	}

}
