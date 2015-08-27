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
function {{@_PLATFORM_NAME_@}}SocketRequest () {

	// Public
	this.method;
	this.requestID;
	this.resource;
	this.payload;
	this.timeout = 30;

	// Private
	this.timerTimeout;


/* ------------ PUBLIC METHODS ------------ */


	/**
	 *  Cancel the request
	 */
	this.cancel = function() {
		this.cancelTimeout();
		if (this.onCancelled) {
			this.onCancelled();
		}
	}

	this.cancelTimeout = function() {
		// Clear the timer
		if (this.timerTimeout) {
			clearTimeout(this.timerTimeout)
		}
	}


	this.setTimeout = function(seconds) {
		// Clear the timer
		this.cancelTimeout();

		// Save the timeout
		this.timeout = seconds;

		// Start the timer
		var self = this;
		this.timerTimeout = setTimeout(function() { timedOut(self); }, seconds * 1000);
	}


/* ------------ PRIVATE METHODS ------------ */


	function timedOut(context) {
		if (context.onTimeout) {
			context.onTimeout();
		}
	}


/* ------------ EVENTS ------------ */


	/**
	 *  Fired if the request times out
	 */
	this.onTimeout = function () {
	}

	/**
	 *  Fired if the request is cancelled
	 */
	this.onCancelled = function () {
	}


}
