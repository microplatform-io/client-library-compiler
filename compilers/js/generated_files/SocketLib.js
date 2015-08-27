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
function {{@_PLATFORM_NAME_@}}SocketLib () {

	// Private
	this.queuedRequests = {};
	this.activeRequests = {};
	this.isIdle = true;
	this.test = 1; // delete this, this is no longer necessary

/* ------------ PUBLIC METHODS ------------ */

	/**
	 *  Cancel all pending requests
	 */
	this.cancelAllRequests = function() {
		for (var requestID in this.activeRequests) {
			var request = this.activeRequests[requestID];
			request.cancel();
		}

		for (var requestID in this.queuedRequests) {
			var request = this.queuedRequests[requestID];
			request.cancel();
		}
	}

	/*
	 *
	 */
	this.cancelRequest = function (request) {
		request.cancelTimeout();
		delete this.activeRequests[request.requestID];
		delete this.queuedRequests[request.requestID];
		checkSocketActivity(this);
	}

	/**
	 *
	 */
	this.connect = function (address) {
		try {
			this.socket = io.connect(address, {
				reconnection: false,
				timeout: {{@_PLATFORM_NAME_@}}Constants.connectionTimeout * 1000,
				upgrade: false,
				forceNew: true
			});
		} catch(err) {
			alert ("Error" + "\n\n" + "Unable to initialize socket. " + err.message);
			return;
		}

		var self = this;
		this.socket.on('connect', function() {
			self.onConnected();
			sendQueuedRequests(self);
		});
		this.socket.on('connect_error', function(error) {
			self.onFailedToConnect(error);
		});
		this.socket.on('disconnect', function() {
			var error = new Error("Disconnected from server");
			self.onDisconnected(error);
		});
		this.socket.on('', function(response) {
			console.log("socket: received");
		});
	}

	/*
	 *
	 */
	this.sendRequest = function (request) {

		// Assign a request ID if the request doesn't already have one
		if (!request.requestID) {
			request.requestID = guid(false);
		}

		// Queue the request if the socket hasn't been initialized just yet
		if (!this.socket) {
			queueRequest(this, request);
			if (this.onShouldReconnectNow) this.onShouldReconnectNow();
			return;
		}
		
		// Queue the request and return IF the socket is not yet connected
		if (!this.socket.connected) {
			queueRequest(this, request);
			if (!this.socket.connecting && !this.socket.reconnecting) {
				// Force the socket to reconnect since its currently idle
				if (this.onShouldReconnectNow) this.onShouldReconnectNow();
			}
			return;
		}

		// Remove the request from the queue (it may not even be queued)
		delete this.queuedRequests[request.requestID];

		// Socket is connected so send the request
		this.activeRequests[request.requestID] = request;
		checkSocketActivity(this);

		// Start/Restart the timeout timer
		request.setTimeout(request.timeout);

		// Setup the response handler
		var self = this;
		this.socket.on(request.requestID, function (reply) {
			reply = JSON.parse(reply);

			// Confirm the request is still alive
			if (!self.activeRequests[request.requestID]) {
				return;
			} else {
				request.cancelTimeout();
				delete self.activeRequests[request.requestID];
				checkSocketActivity(self);
			}

			// Turn the reply into a Request object and return it via the onResponse event
			var response = new {{@_PLATFORM_NAME_@}}SocketRequest();
			response.method = reply.method;
			response.resource = reply.resource;
			response.payload = reply.protobuf;
			if (request.onResponse) {
				request.onResponse(response);
			}

		});

		// Send the request over the socket
		this.socket.emit('request', JSON.stringify({
			request_id: request.requestID,
			method:     request.method,
			resource:   request.resource,
			protobuf:   array_buffer_to_hex(request.payload)
		}));
	}


/* ------------ PRIVATE METHODS ------------ */


	/*
	 *
	 */
	function guid(dashes) {
	    function _p8(s) {
	        var p = (Math.random().toString(16)+"000000000").substr(2,8);
	        return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
	    }
	    return _p8() + _p8(dashes) + _p8(dashes) + _p8();
	}

  /**
   *  Pad a string
   */
  function lpad(string, padString, length) {
    while (string.length < length) {
      string = padString + string;
    }
    return string;
  }

  /**
   *
   */
  function array_buffer_to_hex(array_buffer) {
    var uint8_array = new Uint8Array(array_buffer),
      hex = '';

    for(var i = 0; i < uint8_array.byteLength; i++) {
      hex += lpad(uint8_array[i].toString(16), '0', 2);
    }

    return hex;
  }

	/*
	 *
	 */
	function checkSocketActivity(context) {

		var idle = isObjectEmpty(context.activeRequests);
		if (idle) {
			idle = isObjectEmpty(context.queuedRequests);
		}

		if (idle) {
			if (context.isIdle == false) {
				context.isIdle = true;
				if (context.onSocketActivity) {
					context.onSocketActivity(true);
				}
			} else {
				context.isIdle = true;
			}
		} else {
			if (context.isIdle == true) {
				context.isIdle = false;
				if (context.onSocketActivity) {
					context.onSocketActivity(false);
				}
			} else {
				context.isIdle = false;
			}
		}
	}

	/*
	 *
	 */
	function isObjectEmpty(object) {
	    if ('object' !== typeof object) {
	        return true;
	    }

	    if (null === object) {
	        return true;
	    }

	    if ('undefined' !== Object.keys) {
	        return (0 === Object.keys(object).length);
	    } else {
	        for (var key in object) {
	            if (object.hasOwnProperty(key)) {
	                return false;
	            }
	        }
	        return true;
	    }
	}

	/*
	 *
	 */
	function queueRequest(context, request) {
		// Queue the request if its not already queued
		if (!context.queuedRequests[request.requestID]) {
			context.queuedRequests[request.requestID] = request;
			checkSocketActivity(context);
		}
	}

	/*
	 * Send all the currently queued requests
	 */
	function sendQueuedRequests(context) {
		for (var requestID in context.queuedRequests) {
			context.sendRequest(context.queuedRequests[requestID]);
			delete context.queuedRequests[requestID];
		}
	}

/* ------------ EVENTS ------------ */


	/**
	 *  Fired upon a successful connection
	 */
	this.onConnected = function () {
	}

	/**
	 *  Fired when the socket is disconnected
	 */
	this.onDisconnected = function (error) {
	}

	/**
	 *  Fired when the socket fails to connect
	 */
	this.onFailedToConnect = function (error) {
	}

	/**
	 *  Fired when a request is received from the server
	 */
	this.onRequestReceived = function (request) {
	}

	/**
	 *  Fired when the socket should be told to reconnect immediately
	 */
	this.onShouldReconnectNow = function () {
		
	}
	
	/**
	 *  Fired when the socket goes from an active to idle state or vice versa
	 */
	this.onSocketActivity = function (idle) {
	}


}
