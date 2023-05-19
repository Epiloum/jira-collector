FROM python:3.9-alpine

WORKDIR /app

# Install cron and other dependencies
RUN apk update
RUN apk add --no-cache gcc musl-dev python3-dev mariadb-dev

# Install Packages
RUN pip install mariadb
RUN pip install requests
RUN pip install python-dotenv

# Copy the Python script and cron job file
COPY ./scheduler/*.py .
COPY .env .

CMD ["python", "app.py"]