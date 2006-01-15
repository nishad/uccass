<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id: body_html_reporter.php,v 1.1 2006/01/15 19:43:34 malyvelky Exp $
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/simpletest/reporter.php');
    /**#@-*/
    
    /**
     * Similar as HtmlReporter but it doesn't paint html page beginning/end.
     * Thus you must write yourself '&lt;html&gt;&lt;body&gt;' before running
     * the first test with a BodyHtmlReporter and '&lt;/html&gt;&lt;/body&gt;'
     * after the last one. The advantage is that it can be used multiple times
     * on the same html page.
     * See also paintPageHeader and paintPageFooter.
     * 
     *    Sample minimal test displayer. Generates only
     *    failure messages and a pass count.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
	 *	  @author <a href="mailto:jakubholy@jakubholy.net">Jakub Holy</a>
     */
    class BodyHtmlReporter extends HtmlReporter {
        
        /**
         *    Does nothing yet. The first output will
         *    be sent on the first test start. For use
         *    by a web browser.
         *    @access public
         */
        function BodyHtmlReporter($character_set = 'ISO-8859-1') {
        	$this->HtmlReporter($character_set);
        }
        
        /**
         *    Paints a header for a test run. 
         *    @param string $test_name      Name class of test.
         *    @access public
         */
        function paintHeader($test_name) {
            print "<hr><h1>$test_name</h1>\n";
            flush();
        }
        
        /**
         *    Paints the top of the web page setting the
         *    title to the given name.
         *    @param string $test_name      Title of the page.
         *    @access public
         */
        function paintPageHeader($test_name) {
            $this->sendNoCacheHeaders();
            print "<html>\n<head>\n<title>$test_name</title>\n";
            print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" .
                    $this->_character_set . "\">\n";
            print "<style type=\"text/css\">\n";
            print $this->_getCss() . "\n";
            print "</style>\n";
            print "</head>\n<body>\n";
            flush();
        }
        
        /**
         *    Paints the end of the test with a summary of
         *    the passes and failures.
         *    @param string $test_name        Name class of test.
         *    @access public
         */
        function paintFooter($test_name) {
            $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
            print "<div style=\"";
            print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
            print "\">";
            print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
            print " test cases complete:\n";
            print "<strong>" . $this->getPassCount() . "</strong> passes, ";
            print "<strong>" . $this->getFailCount() . "</strong> fails and ";
            print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
            print "</div>\n";
            print "<hr>\n";
        }
        
        /**
         *    Paints the end of the web page.
         *    @access public
         */
        function paintPageFooter() {
            print "</body>\n</html>\n";
        }
        
        /**
         *    Paints the CSS. Add additional styles here.
         *    @return string            CSS code as text.
         *    @access protected
         */
        function _getCss() {
            return parent::_getCss() . " .message { color: blue; } ";
        }
        
        /**
         *    Paints the test failure with a breadcrumbs
         *    trail of the nesting test suites below the
         *    top level test.
         *    @param string $message    Failure message displayed in
         *                              the context of the other tests.
         *    @access public
         */
        function paintFail($message) {
            parent::paintFail($message);
            print "<span class=\"fail\">Fail</span>: ";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print implode(" -&gt; ", $breadcrumb);
            print " -&gt; " . $this->_htmlEntities($message) . "<br />\n";
        }
        
        /**
         *    Paints a simple supplementary message.
         *    @param string $message        Text to display.
         *    @access public
         */
        function paintMessage($message) {
        	//parent::paintMessage($message);
        	print "<span class=\"message\">Message</span>: ";
        	print " -&gt; " . $this->_htmlEntities($message) . "<br />\n";
        }
    }
?>
