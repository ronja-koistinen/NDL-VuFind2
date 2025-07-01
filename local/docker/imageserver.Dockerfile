FROM alpine:3 AS builder

ARG CANTALOUPE_VERSION=5.0.7

RUN set -eu; \
    apk add --no-cache openjdk17-jdk \
        ca-certificates \
        wget \
        openjpeg-dev \
        lcms2 \
        libpng-dev \
        zstd-dev \
        tiff-dev \
        zlib-dev \
        libwebp-dev \
        perl-image-exiftool \
        maven

WORKDIR /build

RUN set -eu; \
    wget https://github.com/cantaloupe-project/cantaloupe/archive/refs/tags/v$CANTALOUPE_VERSION.tar.gz \
        -O source.tar.gz; \
    tar xzf source.tar.gz --strip-components=1

RUN set -eu; \
    echo "export JAVA_HOME=$(dirname $(dirname $(readlink -f $(which java))))" > ~/.bashrc; \
    mvn package -Dmaven.test.skip

######

FROM alpine:3

ARG CANTALOUPE_VERSION=5.0.7

RUN set -eu; adduser -h /opt/cantaloupe -D cantaloupe; \
    chown -R cantaloupe /opt/cantaloupe; \
    apk add --no-cache openjdk17-jre-headless \
        ca-certificates \
        wget \
        openjpeg \
        lcms2 \
        libpng \
        zstd \
        tiff \
        zlib \
        libwebp \
        perl-image-exiftool; \
    mkdir /etc/cantaloupe

COPY --from=builder /build/target/cantaloupe-$CANTALOUPE_VERSION.jar \
    /opt/cantaloupe/cantaloupe.jar
COPY --from=builder /build/cantaloupe.properties.sample \
    /etc/cantaloupe/cantaloupe.properties

USER cantaloupe
WORKDIR /opt/cantaloupe

CMD ["java", "-Dcantaloupe.config=/etc/cantaloupe/cantaloupe.properties", \
    "-jar", "$home/cantaloupe.jar"]
