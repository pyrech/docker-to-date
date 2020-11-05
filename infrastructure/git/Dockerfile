FROM alpine

RUN apk add --no-cache git

# Fake user to maps with the one on the host
ARG USER_ID
RUN addgroup --gid 1000 app && \
    adduser --system --uid $USER_ID --home /home/app --shell /bin/bash app

RUN mkdir /home/app/sandbox

WORKDIR /home/app/sandbox

CMD tail -f /dev/null
