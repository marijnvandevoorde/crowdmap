FROM node:26-alpine AS build

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

FROM nginx:1.31.7-alpine-slim

COPY --from=build /app/dist/frontend/browser /app/frontend
