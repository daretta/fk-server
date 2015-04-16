# Start from a Debian image with the latest version of Go installed
# and a workspace (GOPATH) configured at /go.
FROM golang

# Copy the local package files to the container's workspace.
ADD ./project/src/fk-server /go/src/pippo/fk-server

# Build the outyet command inside the container.
# (You may fetch or manage dependencies here,
# either manually or with a tool like "godep".)
WORKDIR /go/src/pippo/fk-server
RUN ls -la
RUN go get .
RUN go install pippo/fk-server

# Run the outyet command by default when the container starts.
ENTRYPOINT /go/bin/fk-server

# Document that the service listens on port 8080.
EXPOSE 8080
