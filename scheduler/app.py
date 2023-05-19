import mariadb
import sys
import time
import json
from jira import getFromJIRA
from dotenv import load_dotenv
import os

# load .env
load_dotenv()

# Connect to MariaDB Platform
try:
    conn = mariadb.connect(
        user=os.environ.get('DB_USER'),
        password=os.environ.get('DB_PASSWORD'),
        host=os.environ.get('DB_CONTAINER_NAME'),
        port=3306,
        database=os.environ.get('DB_DATABASE')
    )
except mariadb.Error as e:
    print(f"Error connecting to MariaDB Platform: {e}")
    sys.exit(1)

def procInProgress(conn):
    # Getting issues from respective JIRA software
    res = []
    for cond in json.loads(os.environ.get('JIRA_IN_PROGRESS_CONDITIONS')):
        res += getFromJIRA(cond['domain'], cond['jql'])

    # Init
    keys = []
    cur = conn.cursor()

    try:
        for v in res:
            keys.append(v["k"])
            cur.execute("INSERT INTO issues (k, title) VALUES ('" + v["k"] + "', '" + v["title"] + "') ON DUPLICATE KEY UPDATE title = '" + v["title"] + "'")        

            # 해당 이슈의 진행중 항목 가져오기
            cur.execute("SELECT id, assignee FROM periods WHERE k = '" + v["k"] + "' AND finished = 'n'")
            row = cur.fetchone()

            if row is None:
                # 진행중 항목이 없으면, 진행중 항목 생성
                cur.execute("INSERT INTO periods (k, assignee) VALUES ('" + v["k"] + "', '" + v["assignee"] + "')")        
            else:
                # 담당자 확인
                if v["assignee"] == row[1]:
                    # 담당자가 같으면, 추가처리 없음
                    statement = ""
                else:
                    # 담당자가 다르면, 기존 항목은 종료하고 새로운 진행중 항목 생성
                    statement = ", finished = 'y'"
                    cur.execute("INSERT INTO periods (k, assignee, started_at) VALUES ('" + v["k"] + "', '" + v["assignee"] + "', NOW())")     

                cur.execute("UPDATE periods SET ended_at = NOW()" + statement + " WHERE id = '" + str(row[0]) + "'")
                
        # 더이상 진행중이 아닌 항목은 종료
        cur.execute("UPDATE periods SET finished = 'y' WHERE k NOT IN ('{}')".format("', '".join(map(str, keys))))
        conn.commit()
    except mariadb.Error as e:
        conn.rollback()

def procResolved(conn):
    # Getting issues from respective JIRA software
    res = []
    for cond in json.loads(os.environ.get('JIRA_RESOLVED_CONDITIONS')):
        res += getFromJIRA(cond['domain'], cond['jql'])

    cur = conn.cursor()

    try:
        for v in res:
            # 해당 이슈의 진행중 항목 가져오기
            cur.execute("SELECT id, assignee FROM periods WHERE k = '" + v["k"] + "' LIMIT 1")
            row = cur.fetchone()

            if row is None:
                # 진행중 항목이 없으면, 항목 생성
                cur.execute("INSERT INTO issues (k, title) VALUES ('" + v["k"] + "', '" + v["title"] + "') ON DUPLICATE KEY UPDATE title = '" + v["title"] + "'")        
                cur.execute("INSERT INTO periods (k, assignee, started_at, ended_at, finished) VALUES ('" + v["k"] + "', '" + v["assignee"] + "', NOW(), NOW(), 'y')")        

        conn.commit()
    except mariadb.Error as e:
        conn.rollback()

while True:
    procInProgress(conn)
    procResolved(conn)

    # 60초 경과
    for _ in range(6):
        cur = conn.cursor()
        cur.execute("SELECT 1")
        conn.rollback()
        time.sleep(10)

