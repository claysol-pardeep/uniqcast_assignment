const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs');
const readChunk = require('read-chunk');
const NATS = require('nats');
const MP4Parser = require('./mp4parser.js');
const router = express.Router();
const app = express();

//get the router
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

// parse application/x-www-form-urlencoded
app.use(bodyParser.urlencoded({ extended: false }));

/* NATS Configuration */
const servers = ['nats://nats:4222']; // , 'nats://nats:8222', 'nats://nats:6222'
const nc = NATS.connect({ servers: servers });
nc.on('connect', () => {
	console.log('Connected to ' + nc.currentServer.url.host);
});

/* Message handling */
nc.subscribe('video', function (file) {
	let parser = new MP4Parser(fs.createReadStream(file));
	parser.on('atom', (atom) => {
		var seq = '0' + atom._seq;
		seq = seq.substring(seq.length - 2, seq.length);
		const filePath = './convert-' + new Date().getTime() + '.mp4';
		console.log(
			`${seq}. |${new Array(atom._level * 3).join('-')}${atom.type}(size:${atom.size}, pos:${atom._pos})`
		);
		if (atom.type === 'moov') {
			let status = readChunk(file, 0, atom._pos).then((fileData) => {
				// fs.writeFile(filePath, fileData, function (err) {
				// 	if (err) return console.log(err);
				// });
			});
		}
		nc.publish('video', filePath);
	});
	parser.on('data_mdat', (chunk) => {
		// console.log(chunk.length);
	});
	parser.start();
});

/* Testing the file contents */
app.get('/', function (req, res) {
	const file = 'uploads/1.mp4';
	let parser = new MP4Parser(fs.createReadStream(file));
	parser.on('atom', (atom) => {
		var seq = '0' + atom._seq;
		seq = seq.substring(seq.length - 2, seq.length);
		if (atom.type === 'moov') {
			console.log(
				`${seq}. |${new Array(atom._level * 3).join('-')}${atom.type}(size:${atom.size}, pos:${atom._pos})`
			);
			return readChunk(file, 0, atom._pos).then((fileData) => {
				let filePath = 'uploads/convert-' + new Date().getTime() + '.mp4';
				fs.writeFile(
					filePath,
					fileData,
					{
						encoding: 'base64',
					},
					function (err) {
						if (err) return console.log(err);
						return res.send({ filePath });
					}
				);
			});
		}
		console.log(
			`${seq}. |${new Array(atom._level * 3).join('-')}${atom.type}(size:${atom.size}, pos:${atom._pos})`
		);
	});
	parser.on('data_mdat', (chunk) => {
		console.log(chunk.length);
	});
	parser.start();
});

app.use(router);
const PORT = 3000;

app.listen(PORT, () => {
	console.log(`Server listening on port ${PORT}`);
});
