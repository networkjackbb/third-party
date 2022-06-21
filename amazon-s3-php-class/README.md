This S3 class originated from Donovan Schönknecht's popular S3 php class:

[https://github.com/tpyo/amazon-s3-php-class/]


I found it in the early 2022 timeframe. My main intention was to use this against a MinIO system that we have built to support our clients application. When I went to go start using it, I found several architectural decisions that I felt need revisiting.

--
  Chief among these is the expected use of the class being called statically. I felt this introduced too many issues, so I de-static-fied the entire code base. All methods, and variables are now set/accessed from a real instantiated object. Two main benefit came of this: a) one can now create code that talks to multiple S3 systems at once. b) The main S3 object is injected into an S3Request at instantiation time so that it has access to any variables/methods it might need. This helped to simplify that class as well.

  After this, I made three specific feature additions:
   a) Tagging support
   b) switch to retain path style URIs (bucket in URI, not hostname). MinIO uses path style URIs.
   c) the main body of the response is stored in a "rawbody" variable when being received. It is then exposed as the "body" variable after initial testing for S3 error XML.

  After making these changes and testing to my satisfaction, I went through most of the outstanding pull requests and incorporated where I felt appropriate.
  
  I also made some coding style tweaks. Most of these were to IMO improve readability. Where a single line of code extended across multiple lines, I added some indentation to try and help visually convey that fact.
  
  
Hopefully people find these changes an improvement.

Please feel free to backport anything I've done to the original as your own pull-request. I make no stake license-wise or accept responsibility against any code I've written/changes made.

--
Brian Blood


