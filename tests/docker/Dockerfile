# Stage with musl-compatible ffmpeg
FROM jrottenberg/ffmpeg:4.4-alpine AS ffmpeg

# Your PHP base
FROM php:7.1-alpine

RUN apk add --no-cache lame git unzip libstdc++

# Copy ffmpeg/ffprobe AND their shared libs
COPY --from=ffmpeg /usr/local/bin/ffmpeg /usr/local/bin/ffmpeg
COPY --from=ffmpeg /usr/local/bin/ffprobe /usr/local/bin/ffprobe
COPY --from=ffmpeg /usr/local/lib/ /usr/local/lib/

# Make sure the dynamic loader finds the libs (musl has no ldconfig)
ENV LD_LIBRARY_PATH="/usr/local/lib:${LD_LIBRARY_PATH}"
RUN for f in /usr/local/lib/*.so*; do ln -sf "$f" /usr/lib/; done

RUN apk add --no-cache libgomp

# (optional) create temp dir used by tests
RUN mkdir -p /var/www/waveform/temp && chmod 777 /var/www/waveform/temp

# (optional) composer like before
COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
