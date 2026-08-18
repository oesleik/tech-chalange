#!/usr/bin/env bash
# Importa uma imagem Docker no containerd do k3s do MiniStack.
# Equivalente local ao `eval $(minikube docker-env) && docker build`.
set -euo pipefail

IMAGE="${1:?uso: ministack-load-image.sh <image:tag>}"
REGION="${AWS_REGION:-us-east-1}"
CLUSTER_NAME="${CLUSTER_NAME:-tech-challenge}"
CONTAINER="ministack-eks-${REGION}-${CLUSTER_NAME}"

if ! docker inspect -f '{{.State.Running}}' "${CONTAINER}" 2>/dev/null | grep -q true; then
  echo "Container ${CONTAINER} não está rodando. Rode make aws-local-kubeconfig antes." >&2
  exit 1
fi

echo "Importando ${IMAGE} no k3s (${CONTAINER})..."
docker save "${IMAGE}" | docker exec -i "${CONTAINER}" ctr -n k8s.io images import -
echo "Imagem ${IMAGE} disponível no cluster MiniStack (imagePullPolicy Never)."
