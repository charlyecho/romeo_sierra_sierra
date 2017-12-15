# Romeo Sierra Sierra
A KISS rss feed parser written in PHP

## Requirement
php 5.3.2 or above

## Usage

2 types : 

### by URL
For a http(s) remote file (will use curl for https one) 

    $feed = FeedParser::parseUrl($url);
    
### by File
For a remote or local file (use file_get_contents)

    $feed = FeedParser::parseFile($path);
    
### Features
- parse rss feed
- insert youtube iframe for youtube feeds 

### Tested with
- youtube rss feed (ex : https://www.youtube.com/feeds/videos.xml?channel_id=_the_channel_id_)
- wordpress feed (ex : //en.blog.wordpress.com/feed/)
- blogspot.com (ex : https://account_example.blogspot.com/feeds/posts/default or https://www.blogger.com/feeds/_blod_id_/posts/default)
- feedburner (ex : http://feeds.feedburner.com/_example_)
- spip (ex : http://www._website.tld_/spip.php?page=backend)
- much more
    
## What R. S. S. doesn't do
- caching file
- cleanup content from rss file

## Author
charlyecho <charly@charlyecho.com>

Please give me examples not working in order to improve the stuff =)

## License
MIT License

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