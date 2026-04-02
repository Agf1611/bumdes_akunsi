# Release Tagging

## Tag rilis awal yang direkomendasikan
`v1.0.0`

## Membuat annotated tag
```bash
git tag -a v1.0.0 -m "Initial GitHub-ready release"
git push origin v1.0.0
```

## Rekomendasi aturan versi
- `v1.0.0` rilis baseline stabil pertama
- `v1.0.1` bugfix kecil
- `v1.1.0` penambahan fitur yang kompatibel
- `v2.0.0` perubahan besar atau breaking change

## Langkah release yang direkomendasikan
1. Pastikan branch `main` sudah stabil.
2. Update `CHANGELOG.md`.
3. Update file `VERSION`.
4. Commit perubahan release.
5. Buat annotated tag.
6. Push branch dan tag ke GitHub.
7. Buat GitHub Release memakai isi dari changelog.
