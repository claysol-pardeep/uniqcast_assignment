#!/usr/bin/env node

const yargs = require('yargs');
const axios = require('axios');

const options = yargs
	.usage('Usage: -n <name>')
	.option('path', { alias: 'path', describe: 'Local Video Path', type: 'string' }).argv;

// The url depends on searching or not
const url = 'http://localhost:3000/video';

axios
	.post(
		url,
		{
			url: options.path,
		},
		{ headers: { Accept: 'application/json' } }
	)
	.then((res) => {
		console.log(`VIDEO Url is: ${res} `);
	});
