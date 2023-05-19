import requests
from dotenv import load_dotenv
import os

# load .env
load_dotenv()

# Function getting issues from JIRA
def getFromJIRA(project, jql_query):
    # Jira API endpoint
    url = "https://" + project + ".atlassian.net/rest/api/2/search"

    # Jira credentials
    username = os.environ.get('JIRA_USERNAME')
    password = os.environ.get('JIRA_APITOKEN')

    # Set headers for authentication and response format
    headers = {
        "Content-Type": "application/json",
    }

    # Set query parameters for the API request
    params = {
        "jql": jql_query,
        "maxResults": 200,  # Set the maximum number of results as per your requirement
        "fields": "key,summary,description,status,assignee,reporter",
    }

    # Send the API request
    response = requests.get(url, headers=headers, auth=(username, password), params=params)

    # Process the response
    res = []

    if response.status_code == 200:
        data = response.json()
        issues = data["issues"]
        for issue in issues:
            if issue["fields"]["assignee"] is not None:
                assignee = issue["fields"]["assignee"].get("displayName", "")
            else:
                assignee = ''

            res.append({"k": issue["key"], "status": issue["fields"]["status"]["name"], "assignee": assignee, "title": issue["fields"]["summary"]});
    
    return res
