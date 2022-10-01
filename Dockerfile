FROM debian:buster
LABEL Matthew Horwood <matt@horwood.biz>

COPY . /foxess

RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get -y install python3-pip python3-wheel \
    curl jq vim && \
    groupadd foxess && \
    useradd -d /foxess -g foxess foxess && \
    pip3 install mqttools; \
    chown foxess:foxess /foxess -R;

WORKDIR /foxess
USER foxess

CMD ["/foxess/foxess.sh"]
