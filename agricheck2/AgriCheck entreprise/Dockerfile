FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE 1
ENV PYTHONUNBUFFERED 1

WORKDIR /app

RUN apt-get update && apt-get install -y \
    libpq-dev \
    default-libmysqlclient-dev \
    build-essential \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
RUN pip install --no-cache-dir gunicorn whitenoise

COPY . .

RUN chmod +x manage.py

RUN python manage.py collectstatic --noinput

EXPOSE 8091

CMD ["gunicorn", "--bind", "0.0.0.0:8091", "agricheck.wsgi:application"]
