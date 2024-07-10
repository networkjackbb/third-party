July 10, 2024
---
This is a port of the NodeJS API library for ZestyIO to PHP.

I pulled from two main sources:
	1. The deprecated zestyio-api-wrapper.js found here:
		https://github.com/zesty-io/zestyio-node-api-wrapper
	2. The newer NPMJS oriented repo here:
		https://github.com/zesty-io/node-sdk/

------

Three classes:

1. ZestyIO
	- Reference class provides a InstanceZUID-based Singleton Cache Factory.

2. ZestyIO_Instance
	- All the main methods that 

3. ZestyIO_Util
	- static methods for common utility code. Mostly the HTTP comms based around HTTP verbs/JSON vs Form formatted data.
	- Note, these methods return a HTTPResponse KeyValue Hash. I'll try and document some of the key elements better in subsequent updates.


	
------

Key Difference from the NodeJS version
	- Developer is meant to instantiate a new ZestyIO_Instance object by calling: ZestyIO::ZInstance(instanceZUID), but you can do make your own new object (especially if you want to inject your own Options)
	- Auth is handled directly in the ZestyIO_Instance instead of separately and the Auth Token is stored/referenced therein.


------

I've tried to keep all the method names exactly as they were defined in the original wrapper.js code.


This is freely usable code in any project personal or commercial. I make no guarantees as to the suitability, validity or quality of the code. Caveat Emptor.

As of the moment of me writing this, I have not tested this code at all so there will be fixes/changes coming RSN.


--
Brian Blood
networkjack.info

