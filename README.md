<h1 align="center">
    Money-balancer
</h1>

<p align="center">
    <a href="https://github.com/dorianim/money-balancer/releases/latest">
        <img src="https://img.shields.io/github/v/release/dorianim/money-balancer?logo=github&logoColor=white" alt="GitHub release"/>
    </a>
    <a href="https://www.gnu.org/licenses/agpl-3.0">
        <img src="https://img.shields.io/badge/License-AGPL%20v3-blue.svg" />
    </a>
    <a href="https://github.com/dorianim/money-balancer/actions/workflows/release.yml">
        <img src="https://github.com/dorianim/money-balancer/actions/workflows/release.yml/badge.svg" alt="Badge release image" />
    </a>
</p>

This is meant for situations where multiple people pay for stuff they use together. Whenever someone pays for something, they enter how much they paid. This tool shows everybody if they have paid more or less than they should have.

# Installation
Don't use this. Its just quick and dirty and not really useable. It was created in two hours and not clean or secure. Just ignore this repo.

# Security
This is not secure at all, there are multiple things missing, for example XSS and CSRF Protection. If you are mad enough to use it, make sure to put it behind a forward authentication proxy like [authentik](https://github.com/goauthentik/authentik), [authelia](https://github.com/authelia/authelia) or [oauth2-proyx](https://github.com/oauth2-proxy/oauth2-proxy). Also, you should only let trusted people use this.