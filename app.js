const express = require('express');
const bodyParser = require('body-parser');
const router = express.Router();
const fs = require('fs');
const app = express();
app.use(bodyParser.urlencoded({ extended: false }));

// parse application/x-www-form-urlencoded
app.use(bodyParser.urlencoded({ extended: false }));

// parse application/json
app.use(bodyParser.json());

const NATS = require('nats');
const nc = NATS.connect('nats://localhost:8222');

router.post('/video', (req, res) => {
	let source = req.body.file;
	console.log(`source url is : ${source}`);
	let target = '/Users/apple/assets/' + Date.now() + '.mp4';
	fs.copyFile(source, target, (err) => {
		if (err) throw err;

		fs.stat(target, (err, stat) => {
			// Handle file not found
			if (err !== null && err.code === 'ENOENT') {
				res.sendStatus(404);
			}
			const fileSize = stat.size;
			const range = req.headers.range;
			if (range) {
				const parts = range.replace(/bytes=/, '').split('-');

				const start = parseInt(parts[0], 10);
				const end = parts[1] ? parseInt(parts[1], 10) : fileSize - 1;

				const chunksize = end - start + 1;
				const file = fs.createReadStream(target, { start, end });
				const head = {
					'Content-Range': `bytes ${start}-${end}/${fileSize}`,
					'Accept-Ranges': 'bytes',
					'Content-Length': chunksize,
					'Content-Type': 'video/mp4',
				};
				nc.publish('video-channel', chunksize);
				res.writeHead(206, head);
				file.pipe(res);
			} else {
				const head = {
					'Content-Length': fileSize,
					'Content-Type': 'video/mp4',
				};
				nc.publish('video-channel', target);
				res.writeHead(200, head);
				fs.createReadStream(target).pipe(res);
				///nc.unsubscribe(sid);
			}
		});
	});
});

app.use(router);
const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
	console.log(`Server listening on port ${PORT}`);
});
