FROM node:20-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

FROM nginx:1.29.3-alpine3.22-slim

COPY --from=build /app/dist/frontend/browser /app/frontend
