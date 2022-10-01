FROM alpine:3.16
LABEL Matthew Horwood <matt@horwood.biz>

COPY . /foxess

RUN apk update && \
    apk add py3-pip py3-wheel \
    curl jq bash && \
    pip3 install mqttools

WORKDIR /foxess

CMD ["/foxess/run.sh"]
