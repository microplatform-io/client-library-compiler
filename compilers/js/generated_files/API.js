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
function {{@_PLATFORM_NAME_@}}API () {

	this.connection;
	this.app_id;
	this.product;
	this.token;


/* ------------ PUBLIC METHODS ------------ */

{{@_GENERATED_API_@}}

/* ------------ PRIVATE METHODS ------------ */


/*
	function generateApplicationObject(context) {
		var Application = {{@_PLATFORM_NAME_@}}ProtoBuilder.build("Application");
		var Info = {{@_PLATFORM_NAME_@}}ProtoBuilder.build("Info");

		var info = new Info();
		info.set_uuid(context.app_id);

		var application = new Application();
		application.set_info(info);
		application.set_product(context.product);
		application.set_token(context.token);

		return application;
	}
*/

	function generatePlatformRequestObject(payload) {
		var PlatformRequestObject = {{@_PLATFORM_NAME_@}}PlatformProtoBuilder.build("Request");
		var platformRequest = new PlatformRequestObject();
		platformRequest.set_payload(payload);
		return platformRequest;
	}

}
