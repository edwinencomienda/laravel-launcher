FROM ubuntu:24.04

RUN apt-get update && apt-get install -y curl && apt-get install -y adduser

CMD ["tail", "-f", "/dev/null"]