# ------------------------------------------------------------------
# Core Platform — Frontend Dockerfile (development)
#
# Runs the Vite dev server with hot module replacement.
# Source code is mounted as a volume so edits reflect immediately.
#
# NOT optimised for production — build a multi-stage image later.
# ------------------------------------------------------------------

FROM node:22-alpine

WORKDIR /app

# Install dependencies in a separate layer for better cache reuse.
# The actual source is mounted at runtime, so the COPY below is a
# fallback for the image-level install step only.
COPY package*.json ./
RUN npm install --legacy-peer-deps

# Copy source into the image (overridden by volume mount in compose).
COPY . .

# Vite dev server port
EXPOSE 5173

# server.host=0.0.0.0 required so the port is reachable from outside
# the container. See vite.config.ts for the full server configuration.
CMD ["npm", "run", "dev"]
