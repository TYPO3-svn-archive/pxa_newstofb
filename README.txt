
****************************
*                          *
*  pxa_newstofb user guide   *
*                          *
****************************

1. Create a Facebook application

  - go to http://www.facebook.com/developer then create new application and fill in the necessary information such as
	- application name
	- description
	- user support address
	- and in the tab "Web Site" fill you website url (We will need this url to use for this extension)

2. Requests Authorization code

  - Open the browser and paste the bellow code:

	https://graph.facebook.com/oauth/authorize?client_id=<APP_ID>&scope=email,publish_stream,offline_access,manage_pages&redirect_uri=<YOUR_URL>&display=popup

  Note: there are several options for scope. These are called extended permissions.
  Note: unless you specify offline_access, your tokens will expire as soon as the user signs out of Facebook.

  After you login and allow the application you will redirect to your redirect url with the authorization code like bellow
  <YOUR_URL>?code=68bf44d3d2f6db3ab0f578d1-100000353708620|I9gsHIm2ltWlf4ZWqobnGdUrL9o

  copy the code to paste in extension configuration

  Note: If you remove this application from your profile application setting, and you add the application again then you must generate this code again.


3. Extension cofiguration
	a. Application ID: enter your application id
	b. Application secret: enter your application secret
	c. Fan page ID: Id of the fan page
	d. Group page ID: id of the group page
     Group page can be used to post on private wall aswell. Go to your own wall and look att the URL.
     It says something like http://www.facebook.com/john.doe, use john.doe as your Group page ID to post to your own wall.
	e. Facebook authorize code: the code you get from (2)
	f. Application site url: the site url of your application when you create the application see (1)
	g. Detail news PID: the detail page id of your news page where you have installed the single view of the news.
    h. The UID of tt_news categories that are allowed. If not set, all categories are allowed. Use "," to separate when you have more than one category
	i. Log file path: enter the path of the log file

4. Running the application

  This application is run by shceduler.
  Go to TYPO3 backend -> Scheduler (4.3.x and 4.4.x) and click on add task then select the task "Publish news to Facebook (pxa_newstofb)" then configure the schedule and cront job to run.

  When the shcedule run, the program will select all the news which not yet mark as publish then will publish all these news to fan page and group page.


Done!
