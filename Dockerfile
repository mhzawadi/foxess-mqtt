FROM debian:buster
LABEL Matthew Horwood <matt@horwood.biz>

COPY . /foxess

RUN RUN apk update && \
    apk add py3-pip py3-wheel \
    curl jq && \
    useradd -d /foxess foxess && \
    pip3 install mqttools; \
    chown foxess /foxess -R;

WORKDIR /foxess
USER foxess

CMD ["/foxess/run.sh"]
