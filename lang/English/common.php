<?php

// Language definitions for frequently used strings
$lang_common = array(

// Text orientation and encoding
'lang_direction'		=>	'ltr',	// ltr (Left-To-Right) or rtl (Right-To-Left)
'lang_encoding'			=>	'utf-8',
'lang_multibyte'		=>	true,

// DB client and text conversion encoding
'mysql_encoding'		=>	'utf8',
'mbstring_encoding'		=>	'UTF-8',

// Notices
'Bad request'			=>	'Bad request. The link you followed is incorrect or outdated.',
'No view'			=>	'You do not have permission to view these bords.',
'No permission'			=>	'You do not have permission to access this page.',
'Bad referrer'			=>	'Bad HTTP_REFERER. You were referred to this page from an unauthorized source. If the problem persists please make sure that \'Base URL\' is correctly set in Admin/Options and that you are visiting the board by navigating to that URL. More information regarding the referrer check can be found in the PunBB documentation.',
'Login please'			=>	'Login please!',

// Topic/board indicators
'New icon'			=>	'There are new posts',
'Normal icon'			=>	'<!-- -->',
'Closed icon'			=>	'This topic is closed',
'Redirect icon'			=>	'Redirected board',

// Miscellaneous
'Announcement'			=>	'Announcement',
'List of kinds'			=>	'List of kinds',
'Options'			=>	'Options',
'Actions'			=>	'Actions',
'Submit'			=>	'Submit',	// "name" of submit buttons
'Ban message'			=>	'You are banned from this site.',
'Ban message 2'			=>	'The ban expires at the end of',
'Ban message 3'			=>	'The administrator or moderator that banned you left the following message:',
'Ban message 4'			=>	'Please direct any inquiries to the site administrator at',
'Never'				=>	'Never',
'Today'				=>	'Today',
'Yesterday'			=>	'Yesterday',
'Info'				=>	'Info',		// a common table header
'Go back'			=>	'Go back',
'Maintenance'			=>	'Maintenance',
'Redirecting'			=>	'Redirecting',
'Click redirect'		=>	'Click here if you do not want to wait any longer (or if your browser does not automatically forward you)',
'on'				=>	'on',		// as in "BBCode is on"
'off'				=>	'off',
'yes'				=>	'YES',
'no'				=>	'--',
'add'				=>	'add',
'remove'			=>	'remove',
'Invalid e-mail'		=>	'The e-mail address you entered is invalid.',
'required field'		=>	'is a required field in this form.',	// for javascript form validation
'Last post'			=>	'Last post',
'by'				=>	'by',	// as in last post by someuser
'New posts'			=>	'New&nbsp;posts',	// the link that leads to the first new post (use &nbsp; for spaces)
'New posts info'		=>	'Go to the first new post in this topic.',	// the popup text for new posts links
'Nick'				=>	'Nick',
'Username'			=>	'Username',
'Password'			=>	'Password',
'Remember login'		=>	'Remember me on this computer',
'E-mail'			=>	'E-mail',
'Send e-mail'			=>	'Send e-mail',
'Moderated by'			=>	'Moderated by',
'Registered'			=>	'Since',
'Subject'			=>	'Subject',
'Image'				=>	'Image',
'Description'			=>	'Description',
'Message'			=>	'Message',
'Topic'				=>	'Topic',
'Labels'			=>	'Labels',
'Labels cloud'			=>	'Labels cloud',
'Board'				=>	'Board',
'Category'			=>	'Category',
'Posts'				=>	'Posts',
'Members'			=>	'Members',
'Files'				=>	'Files',
'Bonus'				=>	'Bonus',
'Replies'			=>	'Replies',
'Views'				=>	'Views',
'Author'			=>	'Author',
'Pages'				=>	'Pages',
'BBCode'			=>	'BBCode',	// You probably shouldn't change this
'img tag'			=>	'[img] tag',
'Smilies'			=>	'Smilies',
'and'				=>	'and',
'Image link'			=>	'image',	// This is displayed (i.e. <image>) instead of images when "Show images" is disabled in the profile
'wrote'				=>	'wrote',	// For [quote]'s
'Code'				=>	'Code',		// For [code]'s
'Mailer'			=>	'Mailer',	// As in "MyForums Mailer" in the signature of outgoing e-mails
'Important information'		=>	'Important information',
'Write message legend'		=>	'Write your message and submit',
'continue reading'		=>	'read more&hellip;',	// For cutted message
'Personal menu'			=>	'Personal menu',
'Favorites'			=>	'Favorites',
'Person Info'			=>	'Person Info',
'TOP'				=>	'TOP',
'NEW'				=>	'NEW',

// Title
'Title'				=>	'Title',
'Member'			=>	'Member',	// Default title
'Moderator'			=>	'Moderator',
'Administrator'			=>	'Administrator',
'Banned'			=>	'Banned',
'Guest'				=>	'Guest',

// Stuff for include/parser.php
'BBCode error'			=>	'The BBCode syntax in the message is incorrect.',
'BBCode error 1'		=>	'Missing start tag for [/quote].',
'BBCode error 2'		=>	'Missing end tag for [code].',
'BBCode error 3'		=>	'Missing start tag for [/code].',
'BBCode error 4'		=>	'Missing one or more end tags for [quote].',
'BBCode error 5'		=>	'Missing one or more start tags for [/quote].',

// Stuff for the navigator (top of every page)
'Index'				=>	'Home',
'Board'				=> 'Board',
'Board kind'			=> array(
					PUN_KIND_FORUM	 => 'Forum',
					PUN_KIND_ARTICLE => 'Article',
					PUN_KIND_GALLERY => 'Gallery',
					PUN_KIND_BLOG	 => 'Blog',
					),
'Boards kind'			=> array(
					PUN_KIND_FORUM	 => 'Forums',
					PUN_KIND_ARTICLE => 'Articles',
					PUN_KIND_GALLERY => 'Galleries',
					PUN_KIND_BLOG	 => 'Blogs',
					),
'User list'			=>	'User list',
'Team list'			=>	'Team list',
'Rules'				=>	'Rules',
'Search'			=>	'Search',
'Register'			=>	'Register',
'Login'				=>	'Login',
'Not logged in'			=>	'You are not logged in.',
'Yours'				=>	'Yours',
'Profile'			=>	'Profile',
'Team'				=>	'Team',
'Site map'			=>	'Site map',
'File map'			=>	'File map',
'Attachments'			=>	'Attachments',
'Logout'			=>	'Logout',
'Logged in as'			=>	'Logged in as',
'Admin'				=>	'Administration',
'Last visit'			=>	'Last visit',
'Show new posts'		=>	'New posts since last visit',
'Mark all as read'		=>	'Mark all topics as read',
'Link separator'		=>	'',	// The text that separates links in the navigator
'New reports'			=>	'There are new reports',
'Maintenance mode'		=>	'Maintenance mode!',
'Welcome'			=>	'Welcome!',
'News'				=>	'News',

// Stuff for the page footer
'Board footer'			=>	'Board footer',
'Search links'			=>	'Search links',
'Show recent posts'		=>	'Recent posts',
'Show unanswered posts'		=>	'Unanswered posts',
'Show your posts'		=>	'Your posts',
'Show subscriptions'		=>	'Your Favorites',
'Show friends'			=>	'Your Friends',
'Jump to'			=>	'Jump to',
'Go'				=>	' Go ',		// submit button in board jump
'Move topic'			=>  'Move topic',
'Open topic'			=>  'Open topic',
'Close topic'			=>  'Close topic',
'Unstick topic'			=>  'Unstick topic',
'Stick topic'			=>  'Stick topic',
'Moderate board'		=>	'Moderate board',
'Delete posts'			=>	'Delete multiple posts',
'Debug table'			=>	'Debug information',

// For extern.php RSS feed
'RSS Desc Active'		=>	'The most recently active topics at',	// board_title will be appended to this string
'RSS Desc New'			=>	'The newest topics at',					// board_title will be appended to this string
'Posted'				=>	'Posted'	// The date/time a topic was started

);
