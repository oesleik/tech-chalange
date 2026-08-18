#!/usr/bin/env bash
# Adapter MiniStack: o kubeconfig NÃO vem de `aws eks update-kubeconfig`.
# O EKS emulado é um container k3s
set -euo pipefail

REGION="${AWS_REGION:-us-east-1}"
CLUSTER_NAME="${CLUSTER_NAME:-tech-challenge}"
CONTAINER="ministack-eks-${REGION}-${CLUSTER_NAME}"
OUT="${KUBECONFIG_OUT:-.kube/ministack.yaml}"

echo "Aguardando container k3s ${CONTAINER}..."
for _ in $(seq 1 60); do
  if docker inspect -f '{{.State.Running}}' "${CONTAINER}" 2>/dev/null | grep -q true; then
    break
  fi
  sleep 2
done

if ! docker inspect -f '{{.State.Running}}' "${CONTAINER}" 2>/dev/null | grep -q true; then
  echo "Container ${CONTAINER} não está rodando." >&2
  echo "Se o MiniStack caiu no mock de EKS (sem Docker socket / privilegiado), não há Kubernetes real." >&2
  exit 1
fi

HOST_PORT="$(docker port "${CONTAINER}" 6443/tcp | head -1 | cut -d: -f2)"
if [ -z "${HOST_PORT}" ]; then
  echo "Porta 6443 do k3s não está publicada no host." >&2
  exit 1
fi

mkdir -p "$(dirname "${OUT}")"

for _ in $(seq 1 30); do
  if docker exec "${CONTAINER}" cat /etc/rancher/k3s/k3s.yaml >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

docker exec "${CONTAINER}" cat /etc/rancher/k3s/k3s.yaml \
  | sed "s/127.0.0.1:6443/127.0.0.1:${HOST_PORT}/" \
  > "${OUT}"

chmod 600 "${OUT}"
echo "Kubeconfig (adapter MiniStack) gravado em ${OUT}"
echo "Use: export KUBECONFIG=${OUT}"
