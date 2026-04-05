FROM node:20-alpine AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

ARG NUXT_PUBLIC_API_URL

ENV NUXT_PUBLIC_API_URL=${NUXT_PUBLIC_API_URL}

RUN npm run build

FROM node:20-alpine AS runtime

WORKDIR /app

COPY --from=build /app/.output .output

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
