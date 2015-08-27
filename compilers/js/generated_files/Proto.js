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
var {{@_PLATFORM_NAME_@}}ProtoFiles = { "platform": "{{@_PLATFORM_PROTO_JSON_@}}", "client":  "{{@_CLIENT_PROTO_JSON_@}}"};
var {{@_PLATFORM_NAME_@}}PlatformProtoBuilder = dcodeIO.ProtoBuf.loadProto({{@_PLATFORM_NAME_@}}ProtoFiles.platform);
var {{@_PLATFORM_NAME_@}}ClientProtoBuilder = dcodeIO.ProtoBuf.loadProto({{@_PLATFORM_NAME_@}}ProtoFiles.client);